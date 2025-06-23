<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use App\Services\GeminiAIService;
use Illuminate\Http\Request;

class AIQuizController extends Controller
{
    public function generate(Request $request, $resourceId, GeminiAIService $aiService)
    {
        // Eager load attachments via the many-to-many relationship
        $resource = Resource::with('attachments')->findOrFail($resourceId);

        // Check if there are any attachments
        if ($resource->attachments->isEmpty()) {
            return response()->json(['error' => 'No attachments found for this resource.'], 422);
        }

        // If client specifies an attachment_id query parameter, use it. Otherwise default to first attachment.
        $attachmentId = $request->query('attachment_id');
        if ($attachmentId) {
            $attachment = $resource->attachments->firstWhere('id', $attachmentId);
            if (!$attachment) {
                return response()->json(['error' => 'Attachment not found for this resource.'], 404);
            }
        } else {
            // Default: use the first attachment
            $attachment = $resource->attachments->first();
        }

        if (!$attachment || !$attachment->file_path) {
            return response()->json(['error' => 'Invalid attachment data.'], 422);
        }

        $attachmentPath = storage_path("app/public/{$attachment->file_path}");

        if (!file_exists($attachmentPath)) {

            return response()->json(['error' => 'Attachment file not found.'], 404);
        }

        $mimeType = $attachment->mime_type ?? mime_content_type($attachmentPath) ?? 'application/octet-stream';

        try {
            $quiz = $aiService->generateQuizFromFile($attachmentPath, $mimeType);
            return response()->json(['quiz' => $quiz]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}