<?php
namespace App\Jobs;

use App\Models\Resource;
use App\Services\GeminiAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateAIContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    public function __construct(
        public int $resourceId,
        public int $attachmentId,
        public string $type, // 'quiz' or 'summary'
        public array $parameters,
        public string $jobId
    ) {}

    public function handle(GeminiAIService $aiService)
    {
        try {
            $resource = Resource::with('attachments')->findOrFail($this->resourceId);
            $attachment = $resource->attachments->firstWhere('id', $this->attachmentId);
            
            if (!$attachment) {
                throw new \Exception('Attachment not found');
            }

            $attachmentPath = storage_path("app/public/{$attachment->file_path}");
            if (!file_exists($attachmentPath)) {
                throw new \Exception('Attachment file not found');
            }

            $mimeType = $attachment->mime_type ?? mime_content_type($attachmentPath) ?? 'application/octet-stream';

            if ($this->type === 'quiz') {
                $result = $aiService->generateQuizFromFile(
                    $attachmentPath,
                    $mimeType,
                    $this->parameters['question_count'] ?? 10,
                    $this->parameters['difficulty'] ?? 'medium'
                );
            } else {
                $result = $aiService->generateSummaryFromFile(
                    $attachmentPath,
                    $mimeType,
                    $this->parameters['summary_type'] ?? 'concise',
                    $this->parameters['max_words'] ?? 300
                );
            }

            // Store result in cache with job ID
            Cache::put("ai_job_{$this->jobId}", [
                'status' => 'completed',
                'result' => $result,
                'type' => $this->type,
                'resource_id' => $this->resourceId,
                'attachment_id' => $this->attachmentId,
                'generated_at' => now()->toISOString()
            ], 3600); // 1 hour

        } catch (\Exception $e) {
            Log::error("AI Content Generation Job Failed", [
                'job_id' => $this->jobId,
                'resource_id' => $this->resourceId,
                'type' => $this->type,
                'error' => $e->getMessage()
            ]);

            Cache::put("ai_job_{$this->jobId}", [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'type' => $this->type,
                'resource_id' => $this->resourceId,
                'attachment_id' => $this->attachmentId
            ], 3600);
        }
    }
}