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
        $this->ensurePrivateDisk($disk);
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

    private function ensurePrivateDisk(?string $disk): void
    {
        $settings = config("filesystems.disks.{$disk}");
        $root = $settings['root'] ?? null;

        if (! is_array($settings) || ($settings['driver'] ?? null) !== 'local'
            || ($settings['visibility'] ?? null) !== 'private'
            || ($settings['serve'] ?? false) || isset($settings['url'])
            || ! is_string($root) || ! str_starts_with($root, DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('The upload disk must be a private local disk outside the public web root.');
        }

        $root = realpath($root) ?: $root;

        $publicRoots = [public_path(), storage_path('app/public')];

        foreach (config('filesystems.links', []) as $link => $target) {
            if ($link === public_path() || str_starts_with($link, public_path().DIRECTORY_SEPARATOR)) {
                $publicRoots[] = $target;
            }
        }

        foreach ($publicRoots as $publicRoot) {
            $publicRoot = realpath($publicRoot) ?: $publicRoot;

            if ($root === $publicRoot || str_starts_with($root, $publicRoot.DIRECTORY_SEPARATOR)) {
                throw new RuntimeException('The upload disk must be a private local disk outside the public web root.');
            }
        }
    }
}
