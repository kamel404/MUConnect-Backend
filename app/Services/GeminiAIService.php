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
        $cacheKey = $this->generateCacheKey($filePath, $questionCount, $difficulty, 'quiz');
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

    public function generateSummaryFromFile(
        string $filePath, 
        string $mimeType, 
        string $summaryType = 'concise',
        int $maxWords = 300
    ): array {
        // Input validation for summary
        $this->validateSummaryInputs($filePath, $mimeType, $summaryType, $maxWords);
        
        // Check cache first
        $cacheKey = $this->generateCacheKey($filePath, $maxWords, $summaryType, 'summary');
        if ($cachedSummary = Cache::get($cacheKey)) {
            return $cachedSummary;
        }

        try {
            $summary = $this->processFileAndGenerateSummary($filePath, $mimeType, $summaryType, $maxWords);
            
            // Cache the result
            Cache::put($cacheKey, $summary, self::CACHE_TTL);
            
            return $summary;
            
        } catch (\Exception $e) {
            Log::error('Summary generation failed', [
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

    private function validateSummaryInputs(string $filePath, string $mimeType, string $summaryType, int $maxWords): void
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

        if (!in_array($summaryType, ['concise', 'detailed', 'bullet_points', 'key_concepts'])) {
            throw new \InvalidArgumentException("Summary type must be 'concise', 'detailed', 'bullet_points', or 'key_concepts'");
        }

        if ($maxWords < 50 || $maxWords > 1000) {
            throw new \InvalidArgumentException("Max words must be between 50 and 1000");
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

    private function processFileAndGenerateSummary(string $filePath, string $mimeType, string $summaryType, int $maxWords): array
    {
        if ($mimeType === 'text/plain') {
            // Handle text files directly
            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new \RuntimeException("Failed to read file content");
            }
            return $this->callGeminiAPIWithTextForSummary($content, $summaryType, $maxWords);
        } else {
            // Handle binary files (images, PDFs)
            return $this->callGeminiAPIWithFileForSummary($filePath, $mimeType, $summaryType, $maxWords);
        }
    }

    private function callGeminiAPIWithText(string $textContent, int $questionCount, string $difficulty): array
    {
        $prompt = $this->buildQuizPrompt($questionCount, $difficulty);
        $fullPrompt = $prompt . "\n\nDocument content:\n" . $textContent;
        
        return $this->makeGeminiRequest([
            'contents' => [[
                'parts' => [
                    ['text' => $fullPrompt]
                ]
            ]],
            'generationConfig' => $this->getGenerationConfig()
        ], 'quiz');
    }

    private function callGeminiAPIWithFile(string $filePath, string $mimeType, int $questionCount, string $difficulty): array
    {
        $prompt = $this->buildQuizPrompt($questionCount, $difficulty);
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
        ], 'quiz');
    }

    private function callGeminiAPIWithTextForSummary(string $textContent, string $summaryType, int $maxWords): array
    {
        $prompt = $this->buildSummaryPrompt($summaryType, $maxWords);
        $fullPrompt = $prompt . "\n\nDocument content:\n" . $textContent;
        
        return $this->makeGeminiRequest([
            'contents' => [[
                'parts' => [
                    ['text' => $fullPrompt]
                ]
            ]],
            'generationConfig' => $this->getSummaryGenerationConfig()
        ], 'summary');
    }

    private function callGeminiAPIWithFileForSummary(string $filePath, string $mimeType, string $summaryType, int $maxWords): array
    {
        $prompt = $this->buildSummaryPrompt($summaryType, $maxWords);
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
            'generationConfig' => $this->getSummaryGenerationConfig()
        ], 'summary');
    }

    private function makeGeminiRequest(array $payload, string $responseType = 'quiz'): array
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
                    if ($responseType === 'summary') {
                        return $this->parseGeminiSummaryResponse($response->json());
                    } else {
                        return $this->parseGeminiResponse($response->json());
                    }
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

    private function getSummaryGenerationConfig(): array
    {
        return [
            'temperature' => 0.5,
            'topK' => 30,
            'topP' => 0.8,
            'maxOutputTokens' => 1500,
            'responseMimeType' => 'application/json'
        ];
    }

    private function buildQuizPrompt(int $questionCount, string $difficulty): string
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

    private function buildSummaryPrompt(string $summaryType, int $maxWords): string
{
    return <<<PROMPT
You are an expert educator and summarizer. Your task is to create a **study-friendly structured summary** from the provided document.

Summary Structure:
1. **Introduction paragraph** (2–4 sentences) — explain what the document is about and its overall purpose.
2. **Main Concepts List** — identify the key topics or concepts from the document.
3. **Concept Summaries** — for each concept, write a short paragraph (2–4 sentences) explaining it clearly and concisely, focused on helping students understand and remember.

Style & Tone:
- Write clearly, concisely, and with academic professionalism
- Avoid rephrasing the document line by line
- Use formatting in JSON to make it structured
- Don't exceed {$maxWords} words in total
- Focus on comprehension, not verbosity

Return ONLY valid JSON in the following exact format:
{
  "introduction": "Brief introduction here.",
  "concept_summaries": {
    "Concept 1": "Brief explanation of Concept 1.",
    "Concept 2": "Brief explanation of Concept 2.",
    "Concept 3": "Brief explanation of Concept 3.",
    ...
  },
  "summary_type": "{$summaryType}",
  "word_count": actual_word_count,
  "key_topics": ["Concept 1", "Concept 2", "Concept 3"],
  "confidence_score": 0.9
}

Only return this JSON. Do not include any other text outside the object.
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

    private function parseGeminiSummaryResponse(array $response): array
    {
        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \RuntimeException("Invalid Gemini response structure");
        }

        $text = $response['candidates'][0]['content']['parts'][0]['text'];
        
        // Clean up the response text
        $text = trim($text);
        $text = preg_replace('/^```json\s*/', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        $summary = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in Gemini response: " . json_last_error_msg() . "\nResponse: " . $text);
        }

        return $this->validateSummaryStructure($summary);
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

    private function validateSummaryStructure(array $summary): array
    {
        // Required fields for the new structured summary format
        $requiredFields = ['introduction', 'concept_summaries', 'word_count', 'summary_type'];

        foreach ($requiredFields as $field) {
            if (!isset($summary[$field])) {
                throw new \RuntimeException("Summary is missing required field: {$field}");
            }
        }

        // Validate introduction
        if (!is_string($summary['introduction']) || empty(trim($summary['introduction']))) {
            throw new \RuntimeException("Introduction must be a non-empty string");
        }

        // Validate concept_summaries
        if (!is_array($summary['concept_summaries']) || empty($summary['concept_summaries'])) {
            throw new \RuntimeException("Concept summaries must be a non-empty associative array");
        }

        foreach ($summary['concept_summaries'] as $concept => $explanation) {
            if (!is_string($concept) || empty(trim($concept))) {
                throw new \RuntimeException("Each concept key must be a non-empty string");
            }
            if (!is_string($explanation) || empty(trim($explanation))) {
                throw new \RuntimeException("Explanation for concept '{$concept}' must be a non-empty string");
            }
        }

        // Validate word_count
        if (!is_numeric($summary['word_count']) || $summary['word_count'] < 1) {
            throw new \RuntimeException("Word count must be a positive number");
        }

        // Optional fields validation
        if (isset($summary['key_topics']) && !is_array($summary['key_topics'])) {
            throw new \RuntimeException("Key topics must be an array");
        }

        if (isset($summary['confidence_score']) && (!is_numeric($summary['confidence_score']) || $summary['confidence_score'] < 0 || $summary['confidence_score'] > 1)) {
            throw new \RuntimeException("Confidence score must be a number between 0 and 1");
        }

        return $summary;
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

    private function generateCacheKey(string $filePath, int $param1, string $param2, string $type): string
    {
        return "{$type}_" . md5($filePath . filemtime($filePath) . $param1 . $param2);
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