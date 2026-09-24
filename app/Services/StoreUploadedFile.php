<?php

namespace App\Services;

use App\Models\StoredFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class StoreUploadedFile
{
    public function store(UploadedFile $upload): StoredFile
    {
        $disk = config('filesystems.uploads_disk');
        $mimeType = $upload->getMimeType();
        $extension = $mimeType === 'application/pdf' ? 'pdf' : 'docx';
        $path = $upload->storeAs('documents', Str::uuid().'.'.$extension, $disk);

        if ($path === false) {
            throw new RuntimeException('The uploaded file could not be stored.');
        }

        try {
            return StoredFile::create([
                'original_name' => $upload->getClientOriginalName(),
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $mimeType,
                'size' => $upload->getSize(),
            ]);
        } catch (Throwable $exception) {
            if (! Storage::disk($disk)->delete($path)) {
                throw new RuntimeException('Failed to remove a file after metadata persistence failed.', previous: $exception);
            }

            throw $exception;
        }
    }
}
