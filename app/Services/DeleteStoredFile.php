<?php

namespace App\Services;

use App\Models\StoredFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DeleteStoredFile
{
    public function __construct(private readonly DeletionNotificationPublisher $publisher) {}

    public function delete(int $id, string $source): bool
    {
        [$wasMissing, $publicationFailure] = DB::transaction(function () use ($id, $source): array {
            $file = StoredFile::query()->lockForUpdate()->findOrFail($id);
            $disk = Storage::disk($file->disk);
            $wasMissing = ! $disk->exists($file->path);

            if (! $wasMissing && ! $disk->delete($file->path)) {
                throw new RuntimeException('The physical file could not be deleted.');
            }

            if ($file->deleted_at === null) {
                $file->deleted_at = now();
                $file->deletion_source = $source;
                $file->save();
            }

            try {
                $this->publisher->publish($file, $file->deletion_source, $file->deleted_at);
            } catch (DeletionNotificationPublicationException $exception) {
                return [$wasMissing, $exception];
            }

            $file->delete();

            return [$wasMissing, null];
        });

        if ($publicationFailure !== null) {
            throw $publicationFailure;
        }

        return $wasMissing;
    }
}
