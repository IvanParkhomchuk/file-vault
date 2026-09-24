<?php

namespace App\Http\Controllers;

use App\Services\DeleteStoredFile;
use App\Services\DeletionNotificationPublicationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class FileDeletionController extends Controller
{
    public function __invoke(int $file, DeleteStoredFile $deleteStoredFile): JsonResponse
    {
        try {
            $wasMissing = $deleteStoredFile->delete($file, 'manual');
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'The file was not found.'], 404);
        } catch (DeletionNotificationPublicationException) {
            return response()->json(['message' => 'The file is removed, but its notification is pending. Retry deletion later.'], 503);
        } catch (RuntimeException) {
            return response()->json(['message' => 'Deletion could not be completed. Retry later.'], 500);
        }

        return response()->json(['message' => $wasMissing
            ? 'The file was already missing. Its notification was published and metadata removed.'
            : 'The file was deleted and its notification was published.']);
    }
}
