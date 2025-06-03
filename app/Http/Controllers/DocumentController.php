<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadDocumentRequest;
use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
        
        return response()->json(['message' => 'This is a test response']);

        // dd("uploaded succesfully ");
        // $file = $request->file('document');

        // // Store file in storage
        
        // $filePath = $file->store('documents', 'public');

        // // Save to database
        // $document = Document::create([
        //     'name' => $file->getClientOriginalName(),
        //     'path' => $filePath,
        //     'mime_type' => $file->getClientMimeType(),
        //     'size' => $file->getSize(),
        // ]);

        // return response()->json([
        //     'message' => 'File uploaded successfully',
        //     'document' => $document
        // ], 201);
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
