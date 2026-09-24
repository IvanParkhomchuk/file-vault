<?php

namespace Tests\Feature;

use App\Models\StoredFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StoredFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_metadata_is_persisted_with_an_exact_24_hour_expiration(): void
    {
        $uploadedAt = Carbon::parse('2026-09-24 12:00:00');
        $this->travelTo($uploadedAt);

        $file = StoredFile::create([
            'original_name' => 'report.pdf',
            'disk' => config('filesystems.uploads_disk'),
            'path' => 'documents/random-name.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        $stored = $file->fresh();

        $this->assertSame('report.pdf', $stored->original_name);
        $this->assertSame(config('filesystems.uploads_disk'), $stored->disk);
        $this->assertSame('documents/random-name.pdf', $stored->path);
        $this->assertSame('application/pdf', $stored->mime_type);
        $this->assertSame(1024, $stored->size);
        $this->assertTrue($stored->uploaded_at->equalTo($uploadedAt));
        $this->assertTrue($stored->expires_at->equalTo($uploadedAt->copy()->addDay()));

        $this->travelTo($uploadedAt->copy()->addDay()->subSecond());
        $this->assertFalse(StoredFile::where('expires_at', '<=', now())->exists());

        $this->travelTo($uploadedAt->copy()->addDay());
        $this->assertTrue(StoredFile::where('expires_at', '<=', now())->exists());
    }

    public function test_configured_upload_disk_is_private_and_outside_the_public_directory(): void
    {
        $disk = config('filesystems.uploads_disk');
        $configuration = config("filesystems.disks.{$disk}");

        $this->assertSame('private', $configuration['visibility']);
        $this->assertFalse(str_starts_with($configuration['root'], public_path().DIRECTORY_SEPARATOR));
        $this->assertFalse(str_starts_with($configuration['root'], storage_path('app/public').DIRECTORY_SEPARATOR));
        $this->assertFalse($configuration['serve'] ?? false);

        Storage::fake($disk);
        Storage::disk($disk)->put('storage-check.txt', 'private');
        $this->assertTrue(Storage::disk($disk)->exists('storage-check.txt'));
    }
}
