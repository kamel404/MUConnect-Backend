<?php

namespace App\Jobs;

use App\Models\AIContent;
use App\Services\GeminiAIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateQuizJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 120; // 2 minutes timeout
    public $backoff = [10, 30, 60]; // Retry delays in seconds

    protected $aiContentId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $aiContentId)
    {
        $this->aiContentId = $aiContentId;
    }

    /**
     * Execute the job.
     */
    public function handle(GeminiAIService $aiService): void
    {
        $aiContent = AIContent::findOrFail($this->aiContentId);

        if ($aiContent->status !== 'processing') {
            Log::info("Skipping quiz generation for AI content {$this->aiContentId} - status is {$aiContent->status}");
            return;
        }

        try {
            Log::info("Starting quiz generation for AI content {$this->aiContentId}");

            // Get the attachment path
            $attachment = $aiContent->attachment;
            $attachmentPath = storage_path("app/public/{$attachment->file_path}");

            if (!file_exists($attachmentPath)) {
                throw new \Exception("Attachment file not found: {$attachmentPath}");
            }

            // Get parameters
            $parameters = $aiContent->parameters;
            $mimeType = $attachment->mime_type ?? mime_content_type($attachmentPath) ?? 'application/octet-stream';
            $questionCount = $parameters['question_count'] ?? 10;
            $difficulty = $parameters['difficulty'] ?? 'medium';

            // Generate the quiz
            $quiz = $aiService->generateQuizFromFile(
                $attachmentPath,
                $mimeType,
                $questionCount,
                $difficulty
            );

            // Mark as completed
            $aiContent->markAsCompleted($quiz);

            Log::info("Successfully completed quiz generation for AI content {$this->aiContentId}");

        } catch (\Exception $e) {
            Log::error("Failed to generate quiz for AI content {$this->aiContentId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $aiContent->markAsFailed($e->getMessage());

            // Re-throw to trigger job retry or failure handling
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $aiContent = AIContent::find($this->aiContentId);
        if ($aiContent) {
            $aiContent->markAsFailed($exception->getMessage());
        }

        Log::error("Quiz generation job failed permanently for AI content {$this->aiContentId}", [
            'error' => $exception->getMessage()
        ]);
    }
}
