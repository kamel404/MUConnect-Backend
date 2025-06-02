<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadDocumentRequest;
use App\Models\Document;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

header('Accept: application/json');

class DocumentController extends Controller
{
    /**
     * Upload a document.
    */
    // public function upload(UploadDocumentRequest $request){
    //     dd($request->all(), $request->hasFile('document'));
    // }
    public function upload(UploadDocumentRequest $request)
    {
        try {
            $file = $request->file('document');
            
            $fileContents= file_get_contents($file->getRealPath());
            $checksum = hash('sha256', $fileContents);
            $document = Document::where('checksum', $checksum)->first();
            if (!$document) {
            //If it didn't find any document with the same checksum, it means that the file is new and we can proceed to upload it
            $filePath = $file->store('documents', 'public'); 
            $document = Document::create([
                'title' => $request->input('title', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)),
                'file_path' => $filePath, // Required field
                'mime_type' => $file->getMimeType(), // Required field
                'size' => $file->getSize(), // Required field
                'visibility' => 'private', // Required (default is private)
                // Optional fields
                'user_id' => auth()->id(), // Optional (nullable) //todo for testing
                'description' => $request->input('description'),
            ]);

            return response()->json([
                'message' => 'File uploaded successfully',
                'document' => $document
            ], 201);
            }
            if (!Auth::user()->documents->contains($document->id)) {
                Auth::user()->documents()->attach($document->id);
            }
            else {
                return response()->json([
                    'message' => 'File already exists',
                    'document' => $document
                ], 409);
            }
        } 
        catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all documents.
     */
    public function list()
    {
        return response()->json(Document::all(), 200);
    }

    /**
     * Download a document.
     */
    public function download($id)
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return response()->download(storage_path("app/public/" . $document->path));
    }
}
