<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Services\GeminiAIService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AIQuizController extends Controller
{
    public function generate(Request $request, $resourceId, GeminiAIService $aiService): JsonResponse
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
        
        // Download file from S3 to temporary location
        $tempPath = sys_get_temp_dir() . '/' . uniqid('quiz_') . '_' . basename($attachment->file_path);
        try {
            $fileContents = Storage::disk('s3')->get($attachment->file_path);
            file_put_contents($tempPath, $fileContents);
            $attachmentPath = $tempPath;
        } catch (\Exception $e) {
            return response()->json(['error' => 'Attachment file not found in storage.'], 404);
        }
        
        $mimeType = $attachment->mime_type ?? mime_content_type($attachmentPath) ?? 'application/octet-stream';
        
        $questionCount = $request->query('question_count', 10);
        $difficulty = $request->query('difficulty', 'medium');
        
        // Cache key based on attachment file hash + parameters
        $fileHash = md5_file($attachmentPath);
        $cacheKey = "quiz:{$resourceId}:{$attachment->id}:{$fileHash}:{$questionCount}:{$difficulty}";
        
        try {
            // Cache for 24 hours (86400 seconds)
            $quiz = Cache::remember($cacheKey, 86400, function() use ($aiService, $attachmentPath, $mimeType, $questionCount, $difficulty) {
                return $aiService->generateQuizFromFile($attachmentPath, $mimeType, $questionCount, $difficulty);
            });
            
            return response()->json(['quiz' => $quiz]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function generateSummary(Request $request, $resourceId, GeminiAIService $aiService): JsonResponse
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
        
        // Download file from S3 to temporary location
        $tempPath = sys_get_temp_dir() . '/' . uniqid('summary_') . '_' . basename($attachment->file_path);
        try {
            $fileContents = Storage::disk('s3')->get($attachment->file_path);
            file_put_contents($tempPath, $fileContents);
            $attachmentPath = $tempPath;
        } catch (\Exception $e) {
            return response()->json(['error' => 'Attachment file not found in storage.'], 404);
        }
        
        $mimeType = $attachment->mime_type ?? mime_content_type($attachmentPath) ?? 'application/octet-stream';
        
        $summaryType = $request->query('summary_type', 'concise');
        $maxWords = $request->query('max_words', 300);
        
        // Cache key based on attachment file hash + parameters
        $fileHash = md5_file($attachmentPath);
        $cacheKey = "summary:{$resourceId}:{$attachment->id}:{$fileHash}:{$summaryType}:{$maxWords}";
        
        try {
            // Cache for 24 hours (86400 seconds)
            $summary = Cache::remember($cacheKey, 86400, function() use ($aiService, $attachmentPath, $mimeType, $summaryType, $maxWords) {
                return $aiService->generateSummaryFromFile($attachmentPath, $mimeType, $summaryType, $maxWords);
            });
            
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