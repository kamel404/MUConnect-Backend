<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\RequestException;

class GeminiAIService
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
    private const CACHE_TTL = 3600; // 1 hour
    private const MAX_RETRIES = 3;
    
    private array $supportedMimeTypes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'text/plain'
        // Note: Word documents are not directly supported by Gemini
        // You'll need to convert them to text first
    ];

    public function generateQuizFromFile(
        string $filePath, 
        string $mimeType, 
        int $questionCount = 10,
        string $difficulty = 'medium'
    ): array {
        // Input validation
        $this->validateInputs($filePath, $mimeType, $questionCount, $difficulty);
        
        // Check cache first
        $cacheKey = $this->generateCacheKey($filePath, $questionCount, $difficulty);
        if ($cachedQuiz = Cache::get($cacheKey)) {
            return $cachedQuiz;
        }

        try {
            $quiz = $this->processFileAndGenerateQuiz($filePath, $mimeType, $questionCount, $difficulty);
            
            // Cache the result
            Cache::put($cacheKey, $quiz, self::CACHE_TTL);
            
            return $quiz;
            
        } catch (\Exception $e) {
            Log::error('Quiz generation failed', [
                'file_path' => $filePath,
                'mime_type' => $mimeType,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function validateInputs(string $filePath, string $mimeType, int $questionCount, string $difficulty): void
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new \InvalidArgumentException("File is not readable: {$filePath}");
        }

        if (filesize($filePath) > self::MAX_FILE_SIZE) {
            throw new \InvalidArgumentException("File size exceeds maximum allowed size");
        }

        if (!in_array($mimeType, $this->supportedMimeTypes)) {
            throw new \InvalidArgumentException("Unsupported MIME type: {$mimeType}");
        }

        if ($questionCount < 1 || $questionCount > 20) {
            throw new \InvalidArgumentException("Question count must be between 1 and 20");
        }

        if (!in_array($difficulty, ['easy', 'medium', 'hard'])) {
            throw new \InvalidArgumentException("Difficulty must be 'easy', 'medium', or 'hard'");
        }
    }

    private function processFileAndGenerateQuiz(string $filePath, string $mimeType, int $questionCount, string $difficulty): array
    {
        if ($mimeType === 'text/plain') {
            // Handle text files directly
            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new \RuntimeException("Failed to read file content");
            }
            return $this->callGeminiAPIWithText($content, $questionCount, $difficulty);
        } else {
            // Handle binary files (images, PDFs)
            return $this->callGeminiAPIWithFile($filePath, $mimeType, $questionCount, $difficulty);
        }
    }

    private function callGeminiAPIWithText(string $textContent, int $questionCount, string $difficulty): array
    {
        $prompt = $this->buildPrompt($questionCount, $difficulty);
        $fullPrompt = $prompt . "\n\nDocument content:\n" . $textContent;
        
        return $this->makeGeminiRequest([
            'contents' => [[
                'parts' => [
                    ['text' => $fullPrompt]
                ]
            ]],
            'generationConfig' => $this->getGenerationConfig()
        ]);
    }

    private function callGeminiAPIWithFile(string $filePath, string $mimeType, int $questionCount, string $difficulty): array
    {
        $prompt = $this->buildPrompt($questionCount, $difficulty);
        $fileContent = file_get_contents($filePath);
        
        if ($fileContent === false) {
            throw new \RuntimeException("Failed to read file content");
        }

        return $this->makeGeminiRequest([
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    [
                        'inline_data' => [
                            'mime_type' => $mimeType,
                            'data' => base64_encode($fileContent)
                        ]
                    ]
                ]
            ]],
            'generationConfig' => $this->getGenerationConfig()
        ]);
    }

    private function makeGeminiRequest(array $payload): array
    {
        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $response = Http::timeout(config('http.defaults.timeout', 60))
                    ->withOptions([
                        'verify' => $this->getSSLVerificationPath(),
                        'curl' => [
                            CURLOPT_SSL_VERIFYPEER => true,
                            CURLOPT_SSL_VERIFYHOST => 2,
                            CURLOPT_CAINFO => $this->getCacertPath(),
                        ],
                    ])
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                    ])
                    ->post($this->getGeminiEndpoint(), $payload);

                if ($response->successful()) {
                    return $this->parseGeminiResponse($response->json());
                }

                Log::warning("Gemini API attempt {$attempt} failed", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                if ($attempt === self::MAX_RETRIES) {
                    throw new \RuntimeException("Gemini API failed after {$attempt} attempts: " . $response->body());
                }

            } catch (RequestException $e) {
                Log::warning("Gemini API request exception on attempt {$attempt}", [
                    'message' => $e->getMessage()
                ]);

                if ($attempt === self::MAX_RETRIES) {
                    throw new \RuntimeException("Gemini API request failed: " . $e->getMessage());
                }
            }

            // Exponential backoff
            sleep(pow(2, $attempt - 1));
        }

        throw new \RuntimeException("Unexpected error in API call loop");
    }

    private function getGenerationConfig(): array
    {
        return [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.8,
            'maxOutputTokens' => 2048,
            'responseMimeType' => 'application/json'
        ];
    }

    private function buildPrompt(int $questionCount, string $difficulty): string
    {
        return <<<PROMPT
You are an expert educational content creator. Analyze the provided document and create exactly {$questionCount} multiple-choice questions at {$difficulty} difficulty level.

Requirements:
- Questions should test comprehension, not just memorization
- Each question must have exactly 4 options (A, B, C, D)
- Only one correct answer per question
- Avoid ambiguous or trick questions
- Cover different aspects of the content
- Make distractors plausible but clearly incorrect

Difficulty guidelines:
- Easy: Basic recall and understanding
- Medium: Application and analysis
- Hard: Synthesis and evaluation

Return ONLY valid JSON in this exact format:
[
  {
    "question": "Clear, specific question text?",
    "options": ["Option A", "Option B", "Option C", "Option D"],
    "correct_answer": "Option A",
    "explanation": "Brief explanation of why this is correct"
  }
]

Do not include any text before or after the JSON array.
PROMPT;
    }

    private function parseGeminiResponse(array $response): array
    {
        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \RuntimeException("Invalid Gemini response structure");
        }

        $text = $response['candidates'][0]['content']['parts'][0]['text'];
        
        // Clean up the response text
        $text = trim($text);
        $text = preg_replace('/^```json\s*/', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        $quiz = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in Gemini response: " . json_last_error_msg() . "\nResponse: " . $text);
        }

        return $this->validateQuizStructure($quiz);
    }

    private function validateQuizStructure(array $quiz): array
    {
        if (!is_array($quiz) || empty($quiz)) {
            throw new \RuntimeException("Quiz must be a non-empty array");
        }

        foreach ($quiz as $index => $question) {
            if (!isset($question['question'], $question['options'], $question['correct_answer'])) {
                throw new \RuntimeException("Question {$index} is missing required fields");
            }

            if (!is_array($question['options']) || count($question['options']) !== 4) {
                throw new \RuntimeException("Question {$index} must have exactly 4 options");
            }

            if (!in_array($question['correct_answer'], $question['options'])) {
                throw new \RuntimeException("Question {$index} correct answer must be one of the options");
            }
        }

        return $quiz;
    }

    private function getGeminiEndpoint(): string
    {
        $apiKey = config('services.gemini.api_key');
        
        if (empty($apiKey)) {
            throw new \RuntimeException("Gemini API key not configured");
        }

        // Using the newer Gemini 1.5 Flash model
        return "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";
    }

    private function generateCacheKey(string $filePath, int $questionCount, string $difficulty): string
    {
        return 'quiz_' . md5($filePath . filemtime($filePath) . $questionCount . $difficulty);
    }

    /**
     * Get SSL verification configuration
     */
    private function getSSLVerificationPath(): bool|string
    {
        // Always use the cacert.pem file if it exists
        $cacertPath = $this->getCacertPath();
        if (!empty($cacertPath)) {
            return $cacertPath;
        }
        
        // Fallback to configuration
        return config('http.ssl_verify', true);
    }

    /**
     * Get the path to cacert.pem file
     */
    private function getCacertPath(): string
    {
        // Try to find cacert.pem in common locations
        $paths = [
            storage_path('cacert.pem'),
            base_path('cacert.pem'),
            '/etc/ssl/certs/ca-certificates.crt', // Ubuntu/Debian
            '/etc/pki/tls/certs/ca-bundle.crt',   // CentOS/RHEL
            '/usr/local/share/certs/ca-certificates.crt', // FreeBSD
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // If no cacert found, return empty string to use system default
        return '';
    }
}