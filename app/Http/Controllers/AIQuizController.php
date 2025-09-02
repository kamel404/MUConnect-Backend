<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateQuizJob;
use App\Jobs\GenerateSummaryJob;
use App\Models\AIContent;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;

class AIQuizController extends Controller
{
    public function generate(Request $request, $resourceId): JsonResponse
    {
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

        $attachmentPath = storage_path("app/public/{$attachment->file_path}");
        if (!file_exists($attachmentPath)) {
            return response()->json(['error' => 'Attachment file not found.'], 404);
        }

        // Get quiz parameters from request
        $questionCount = $request->query('question_count', 10);
        $difficulty = $request->query('difficulty', 'medium');

        // Create AI content record
        $aiContent = AIContent::create([
            'type' => 'quiz',
            'resource_id' => $resourceId,
            'attachment_id' => $attachment->id,
            'user_id' => Auth::id(),
            'parameters' => [
                'question_count' => $questionCount,
                'difficulty' => $difficulty
            ],
            'status' => 'processing'
        ]);

        // Dispatch the job
        GenerateQuizJob::dispatch($aiContent->id);

        return response()->json([
            'message' => 'Quiz generation started. Check status using the content ID.',
            'content_id' => $aiContent->id,
            'status' => 'processing'
        ], 202);
    }

    public function generateSummary(Request $request, $resourceId): JsonResponse
    {
        // Validate request parameters
        $request->validate([
            'attachment_id' => 'sometimes|integer',
            'summary_type' => 'sometimes|string|in:concise,detailed,bullet_points,key_concepts',
            'max_words' => 'sometimes|integer|min:50|max:1000'
        ]);

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

        $attachmentPath = storage_path("app/public/{$attachment->file_path}");
        if (!file_exists($attachmentPath)) {
            return response()->json(['error' => 'Attachment file not found.'], 404);
        }

        // Get summary parameters from request
        $summaryType = $request->query('summary_type', 'concise');
        $maxWords = $request->query('max_words', 300);

        // Create AI content record
        $aiContent = AIContent::create([
            'type' => 'summary',
            'resource_id' => $resourceId,
            'attachment_id' => $attachment->id,
            'user_id' => Auth::id(),
            'parameters' => [
                'summary_type' => $summaryType,
                'max_words' => $maxWords
            ],
            'status' => 'processing'
        ]);

        // Dispatch the job
        GenerateSummaryJob::dispatch($aiContent->id);

        return response()->json([
            'message' => 'Summary generation started. Check status using the content ID.',
            'content_id' => $aiContent->id,
            'status' => 'processing'
        ], 202);
    }

    public function getContentStatus($contentId): JsonResponse
    {
        $aiContent = AIContent::with(['resource', 'attachment'])->findOrFail($contentId);

        // Check if user owns this content
        if ($aiContent->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $response = [
            'content_id' => $aiContent->id,
            'type' => $aiContent->type,
            'status' => $aiContent->status,
            'created_at' => $aiContent->created_at,
            'resource' => [
                'id' => $aiContent->resource->id,
                'title' => $aiContent->resource->title
            ],
            'attachment' => [
                'id' => $aiContent->attachment->id,
                'original_name' => $aiContent->attachment->original_name
            ]
        ];

        if ($aiContent->isCompleted()) {
            $response['content'] = $aiContent->content;
            $response['completed_at'] = $aiContent->completed_at;
        } elseif ($aiContent->isFailed()) {
            $response['error'] = $aiContent->error_message;
        }

        return response()->json($response);
    }

    public function generateBatch(Request $request, $resourceId): JsonResponse
    {
        $request->validate([
            'operations' => 'required|array|min:1|max:5',
            'operations.*.type' => 'required|in:quiz,summary',
            'operations.*.attachment_id' => 'sometimes|integer',
            'operations.*.parameters' => 'sometimes|array'
        ]);

        $resource = Resource::with('attachments')->findOrFail($resourceId);

        if ($resource->attachments->isEmpty()) {
            return response()->json(['error' => 'No attachments found for this resource.'], 422);
        }

        $jobs = [];
        $contentIds = [];

        foreach ($request->operations as $operation) {
            $attachmentId = $operation['attachment_id'] ?? null;
            $attachment = $this->getAttachment($resource, $attachmentId);

            if (!$attachment) {
                continue; // Skip invalid attachments
            }

            $attachmentPath = storage_path("app/public/{$attachment->file_path}");
            if (!file_exists($attachmentPath)) {
                continue; // Skip missing files
            }

            // Create AI content record
            $aiContent = AIContent::create([
                'type' => $operation['type'],
                'resource_id' => $resourceId,
                'attachment_id' => $attachment->id,
                'user_id' => Auth::id(),
                'parameters' => $operation['parameters'] ?? [],
                'status' => 'processing'
            ]);

            $contentIds[] = $aiContent->id;

            // Add job to batch
            if ($operation['type'] === 'quiz') {
                $jobs[] = new GenerateQuizJob($aiContent->id);
            } else {
                $jobs[] = new GenerateSummaryJob($aiContent->id);
            }
        }

        if (empty($jobs)) {
            return response()->json(['error' => 'No valid operations to process.'], 422);
        }

        // Create and dispatch job batch
        $batch = Bus::batch($jobs)->dispatch();

        return response()->json([
            'message' => 'Batch processing started.',
            'batch_id' => $batch->id,
            'content_ids' => $contentIds,
            'total_operations' => count($jobs)
        ], 202);
    }

    public function getBatchStatus($batchId): JsonResponse
    {
        $batch = Bus::findBatch($batchId);

        if (!$batch) {
            return response()->json(['error' => 'Batch not found.'], 404);
        }

        return response()->json([
            'batch_id' => $batch->id,
            'total_jobs' => $batch->totalJobs,
            'pending_jobs' => $batch->pendingJobs,
            'failed_jobs' => $batch->failedJobs,
            'finished_at' => $batch->finishedAt,
            'cancelled_at' => $batch->cancelledAt,
            'progress' => $batch->progress()
        ]);
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