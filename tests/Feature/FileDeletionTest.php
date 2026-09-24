<?php

namespace Tests\Feature;

use App\Models\StoredFile;
use App\Services\DeleteStoredFile;
use App\Services\DeletionNotificationPublicationException;
use App\Services\DeletionNotificationPublisher;
use DateTimeInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class FileDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.uploads_disk'));
    }

    public function test_manual_deletion_removes_file_and_metadata_and_publishes_notification(): void
    {
        $file = $this->storedFile();
        $publisher = Mockery::mock(DeletionNotificationPublisher::class);
        $publisher->shouldReceive('publish')->once()->withArgs(function (StoredFile $published, string $source, DateTimeInterface $at) use ($file): bool {
            return $published->id === $file->id && $source === 'manual' && $at->getTimestamp() === now()->getTimestamp();
        });
        $this->app->instance(DeletionNotificationPublisher::class, $publisher);

        $this->deleteJson(route('files.destroy', $file))->assertOk()
            ->assertJsonPath('message', 'The file was deleted and its notification was published.');

        Storage::disk($file->disk)->assertMissing($file->path);
        $this->assertDatabaseMissing('stored_files', ['id' => $file->id]);
    }

    public function test_repeated_deletion_returns_not_found_without_another_notification(): void
    {
        $file = $this->storedFile();
        $publisher = Mockery::mock(DeletionNotificationPublisher::class);
        $publisher->shouldReceive('publish')->once();
        $this->app->instance(DeletionNotificationPublisher::class, $publisher);

        $this->deleteJson(route('files.destroy', $file))->assertOk();
        $this->deleteJson(route('files.destroy', $file))->assertNotFound();
    }

    public function test_missing_physical_file_is_reconciled_and_published(): void
    {
        $file = $this->storedFile();
        Storage::disk($file->disk)->delete($file->path);
        $publisher = Mockery::mock(DeletionNotificationPublisher::class);
        $publisher->shouldReceive('publish')->once()->withArgs(fn (StoredFile $published, string $source) => $published->id === $file->id && $source === 'manual');
        $this->app->instance(DeletionNotificationPublisher::class, $publisher);

        $this->deleteJson(route('files.destroy', $file))->assertOk()
            ->assertJsonPath('message', 'The file was already missing. Its notification was published and metadata removed.');

        $this->assertDatabaseMissing('stored_files', ['id' => $file->id]);
    }

    public function test_filesystem_failure_retains_file_and_metadata_without_publishing(): void
    {
        $file = $this->storedFile();
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('exists')->once()->with($file->path)->andReturn(true);
        $disk->shouldReceive('delete')->once()->with($file->path)->andReturn(false);
        Storage::shouldReceive('disk')->once()->with($file->disk)->andReturn($disk);
        $publisher = Mockery::mock(DeletionNotificationPublisher::class);
        $publisher->shouldNotReceive('publish');
        $this->app->instance(DeletionNotificationPublisher::class, $publisher);

        $this->deleteJson(route('files.destroy', $file))->assertInternalServerError()
            ->assertJsonPath('message', 'Deletion could not be completed. Retry later.');

        $this->assertDatabaseHas('stored_files', ['id' => $file->id, 'deleted_at' => null]);
    }

    public function test_publication_failure_keeps_recovery_state_and_retry_reuses_original_event(): void
    {
        $file = $this->storedFile();
        $firstTime = now();
        $publisher = Mockery::mock(DeletionNotificationPublisher::class);
        $publisher->shouldReceive('publish')->once()->andThrow(new DeletionNotificationPublicationException('Broker unavailable'));
        $this->app->instance(DeletionNotificationPublisher::class, $publisher);

        $this->deleteJson(route('files.destroy', $file))->assertServiceUnavailable()
            ->assertJsonPath('message', 'The file is removed, but its notification is pending. Retry deletion later.');

        Storage::disk($file->disk)->assertMissing($file->path);
        $this->assertDatabaseHas('stored_files', [
            'id' => $file->id,
            'deletion_source' => 'manual',
        ]);
        $this->assertSame($firstTime->getTimestamp(), $file->fresh()->deleted_at->getTimestamp());

        $this->travel(5)->minutes();
        $retryPublisher = Mockery::mock(DeletionNotificationPublisher::class);
        $retryPublisher->shouldReceive('publish')->once()->withArgs(function (StoredFile $published, string $source, DateTimeInterface $at) use ($file, $firstTime): bool {
            return $published->id === $file->id && $source === 'manual' && $at->getTimestamp() === $firstTime->getTimestamp();
        });
        $this->app->instance(DeletionNotificationPublisher::class, $retryPublisher);

        $this->assertTrue(app(DeleteStoredFile::class)->delete($file->id, 'expiration'));
        $this->assertDatabaseMissing('stored_files', ['id' => $file->id]);
    }

    private function storedFile(): StoredFile
    {
        $disk = config('filesystems.uploads_disk');
        $path = 'documents/example.pdf';
        Storage::disk($disk)->put($path, '%PDF-1.4');

        return StoredFile::create([
            'original_name' => 'example.pdf',
            'disk' => $disk,
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 8,
        ]);
    }
}
