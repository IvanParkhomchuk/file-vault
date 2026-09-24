<?php

namespace App\Http\Controllers;

use App\Services\StoreUploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;

class FileUploadController extends Controller
{
    public function __invoke(Request $request, StoreUploadedFile $storeUploadedFile): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', File::types(['pdf', 'docx'])->extensions(['pdf', 'docx'])->max(10 * 1024)],
        ]);

        $file = $storeUploadedFile->store($validated['file']);

        return response()->json([
            'id' => $file->id,
            'original_name' => $file->original_name,
            'mime_type' => $file->mime_type,
            'size' => $file->size,
            'uploaded_at' => $file->uploaded_at->toIso8601String(),
            'expires_at' => $file->expires_at->toIso8601String(),
        ], 201);
    }
}
