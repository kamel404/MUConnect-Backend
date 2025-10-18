<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Services\GeminiAIService;
use App\Jobs\GenerateAIContentJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AIQuizController extends Controller
{
    public function generate(Request $request, $resourceId, GeminiAIService $aiService): JsonResponse
    {
        // Check if we should use async processing
        $async = $request->query('async', true);
        
        if (!$async) {
            return $this->generateSync($request, $resourceId, $aiService);
        }

        // Eager load attachments via the many-to-many relationship
        $resource = Resource::with('attachments')->findOrFail($resourceId);
        
        // Check if there are any attachments
        if ($resource->attachments->isEmpty()) {
            return response()->json(['error' => 'No attachments found for this resource.'], 422);
        }
        
        // If client specifies an attachment_id query parameter, use it. Otherwise default to first attachment.
        $attachmentId = $request->query('attachment_id');
        $attachment = $this->getAttachment($resource, $attachmentId);
        
        if (!$attachment) {
            return response()->json(['error' => 'Attachment not found for this resource.'], 404);
        }

        // Generate unique job ID
        $jobId = Str::uuid()->toString();
        
        // Get quiz parameters from request
        $parameters = [
            'question_count' => $request->query('question_count', 10),
            'difficulty' => $request->query('difficulty', 'medium')
        ];

        // Dispatch job
        GenerateAIContentJob::dispatch($resourceId, $attachment->id, 'quiz', $parameters, $jobId);

        return response()->json([
            'job_id' => $jobId,
            'status' => 'processing',
            'message' => 'Quiz generation started. Use the job_id to check status.',
            'check_url' => route('ai.job.status', ['jobId' => $jobId])
        ]);
    }

    public function generateSummary(Request $request, $resourceId, GeminiAIService $aiService): JsonResponse
    {
        // Validate request parameters
        $request->validate([
            'attachment_id' => 'sometimes|integer',
            'summary_type' => 'sometimes|string|in:concise,detailed,bullet_points,key_concepts',
            'max_words' => 'sometimes|integer|min:50|max:1000'
        ]);

        // Check if we should use async processing
        $async = $request->query('async', true);
        
        if (!$async) {
            return $this->generateSummarySync($request, $resourceId, $aiService);
        }

        // Eager load attachments via the many-to-many relationship
        $resource = Resource::with('attachments')->findOrFail($resourceId);
        
        // Check if there are any attachments
        if ($resource->attachments->isEmpty()) {
            return response()->json(['error' => 'No attachments found for this resource.'], 422);
        }
        
        // If client specifies an attachment_id query parameter, use it. Otherwise default to first attachment.
        $attachmentId = $request->query('attachment_id');
        $attachment = $this->getAttachment($resource, $attachmentId);
        
        if (!$attachment) {
            return response()->json(['error' => 'Attachment not found for this resource.'], 404);
        }

        // Generate unique job ID
        $jobId = Str::uuid()->toString();
        
        // Get summary parameters from request
        $parameters = [
            'summary_type' => $request->query('summary_type', 'concise'),
            'max_words' => $request->query('max_words', 300)
        ];

        // Dispatch job
        GenerateAIContentJob::dispatch($resourceId, $attachment->id, 'summary', $parameters, $jobId);

        return response()->json([
            'job_id' => $jobId,
            'status' => 'processing',
            'message' => 'Summary generation started. Use the job_id to check status.',
            'check_url' => route('ai.job.status', ['jobId' => $jobId])
        ]);
    }

    public function checkJobStatus($jobId): JsonResponse
    {
        $result = Cache::get("ai_job_{$jobId}");
        
        if (!$result) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Job not found or expired'
            ], 404);
        }

        if ($result['status'] === 'completed') {
            // Remove from cache after successful retrieval
            Cache::forget("ai_job_{$jobId}");
        }

        return response()->json($result);
    }

    // Keep the original sync methods for backward compatibility
    private function generateSync(Request $request, $resourceId, GeminiAIService $aiService): JsonResponse
    {
        // Original synchronous implementation
        $resource = Resource::with('attachments')->findOrFail($resourceId);
        
        if ($resource->attachments->isEmpty()) {
            return response()->json(['error' => 'No attachments found for this resource.'], 422);
        }
        
        $attachmentId = $request->query('attachment_id');
        $attachment = $this->getAttachment($resource, $attachmentId);
        
        if (!$attachment) {
            return response()->json(['error' => 'Attachment not found for this resource.'], 404);
        }
        
        $attachmentPath = storage_path("app/public/{$attachment->file_path}");
        if (!file_exists($attachmentPath)) {
            return response()->json(['error' => 'Attachment file not found.'], 404);
        }
        
        $mimeType = $attachment->mime_type ?? mime_content_type($attachmentPath) ?? 'application/octet-stream';
        
        $questionCount = $request->query('question_count', 10);
        $difficulty = $request->query('difficulty', 'medium');
        
        try {
            $quiz = $aiService->generateQuizFromFile($attachmentPath, $mimeType, $questionCount, $difficulty);
            return response()->json(['quiz' => $quiz]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function generateSummarySync(Request $request, $resourceId, GeminiAIService $aiService): JsonResponse
    {
        // Original synchronous implementation for summary
        $resource = Resource::with('attachments')->findOrFail($resourceId);
        
        if ($resource->attachments->isEmpty()) {
            return response()->json(['error' => 'No attachments found for this resource.'], 422);
        }
        
        $attachmentId = $request->query('attachment_id');
        $attachment = $this->getAttachment($resource, $attachmentId);
        
        if (!$attachment) {
            return response()->json(['error' => 'Attachment not found for this resource.'], 404);
        }
        
        $attachmentPath = storage_path("app/public/{$attachment->file_path}");
        if (!file_exists($attachmentPath)) {
            return response()->json(['error' => 'Attachment file not found.'], 404);
        }
        
        $mimeType = $attachment->mime_type ?? mime_content_type($attachmentPath) ?? 'application/octet-stream';
        
        $summaryType = $request->query('summary_type', 'concise');
        $maxWords = $request->query('max_words', 300);
        
        try {
            $summary = $aiService->generateSummaryFromFile($attachmentPath, $mimeType, $summaryType, $maxWords);
            
            $response = [
                'summary' => $summary,
                'source' => [
                    'resource_id' => $resourceId,
                    'attachment_id' => $attachment->id,
                    'file_name' => $attachment->original_name ?? basename($attachment->file_path),
                    'mime_type' => $mimeType,
                    'file_size' => filesize($attachmentPath)
                ],
                'generated_at' => now()->toISOString()
            ];
            
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper method to get attachment from resource
     */
    private function getAttachment($resource, $attachmentId = null)
    {
        if ($attachmentId) {
            $attachment = $resource->attachments->firstWhere('id', $attachmentId);
            if (!$attachment) {
                return null;
            }
        } else {
            // Default: use the first attachment
            $attachment = $resource->attachments->first();
        }

        if (!$attachment || !$attachment->file_path) {
            return null;
        }

        return $attachment;
    }
}