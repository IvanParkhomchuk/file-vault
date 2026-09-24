<?php

namespace App\Console\Commands;

use App\Models\StoredFile;
use App\Services\DeleteStoredFile;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteExpiredFiles extends Command
{
    protected $signature = 'files:delete-expired';

    protected $description = 'Delete expired files and retry pending deletion notifications';

    public function handle(DeleteStoredFile $deleteStoredFile): int
    {
        $cutoff = now();
        $failed = false;

        StoredFile::query()
            ->where(function (Builder $query) use ($cutoff): void {
                $query->where('expires_at', '<=', $cutoff)
                    ->orWhereNotNull('deleted_at');
            })
            ->chunkById(100, function ($files) use ($deleteStoredFile, &$failed): void {
                foreach ($files as $file) {
                    try {
                        $deleteStoredFile->delete($file->id, 'expiration');
                    } catch (ModelNotFoundException) {
                        // A concurrent manual deletion already completed this record.
                    } catch (Throwable $exception) {
                        $failed = true;
                        Log::error('Automatic file deletion failed.', [
                            'file_id' => $file->id,
                            'exception' => $exception,
                        ]);
                        $this->error("Failed to delete file {$file->id}: {$exception->getMessage()}");
                    }
                }
            });

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
