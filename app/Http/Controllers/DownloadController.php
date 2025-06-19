<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function download($type, $filename)
    {
        // Sanitize the filename to prevent directory traversal
        $sanitizedFilename = basename($filename);
        
        // Validate file type and construct the path accordingly
        $validTypes = ['documents', 'images', 'videos'];
        
        if (!in_array($type, $validTypes)) {
            return response()->json(['message' => 'Invalid file type.'], 400);
        }
        
        $filePath = "attachments/{$type}/{$sanitizedFilename}";

        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        $file = Storage::disk('public')->path($filePath);
        $mimeType = Storage::disk('public')->mimeType($filePath);

        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $sanitizedFilename . '"',
        ];

        return response()->download($file, $sanitizedFilename, $headers);
    }
}
