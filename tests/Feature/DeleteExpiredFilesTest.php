<?php

namespace Tests\Feature;

use App\Models\StoredFile;
use App\Services\DeletionNotificationPublicationException;
use App\Services\DeletionNotificationPublisher;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class DeleteExpiredFilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.uploads_disk'));
        $this->travelTo(Carbon::parse('2026-09-24 12:00:00'));
    }

    public function test_file_expires_at_exactly_24_hours_and_is_published_once(): void
    {
        $file = $this->storedFile('boundary.pdf');
        $publisher = Mockery::mock(DeletionNotificationPublisher::class);
        $publisher->shouldReceive('publish')->once()->withArgs(function (StoredFile $published, string $source, DateTimeInterface $at) use ($file): bool {
            return $published->id === $file->id
                && $source === 'expiration'
                && $at->getTimestamp() === now()->getTimestamp();
        });
        $this->app->instance(DeletionNotificationPublisher::class, $publisher);

        $this->travelTo(Carbon::parse('2026-09-25 11:59:59'));
        $this->artisan('files:delete-expired')->assertSuccessful();
        Storage::disk($file->disk)->assertExists($file->path);
        $this->assertDatabaseHas('stored_files', ['id' => $file->id]);

        $this->travelTo(Carbon::parse('2026-09-25 12:00:00'));
        $this->artisan('files:delete-expired')->assertSuccessful();
        Storage::disk($file->disk)->assertMissing($file->path);
        $this->assertDatabaseMissing('stored_files', ['id' => $file->id]);

        $this->artisan('files:delete-expired')->assertSuccessful();
    }

    public function test_nonexpired_file_is_not_deleted_when_another_file_is_expired(): void
    {
        $expired = $this->storedFile('expired.pdf');
        $this->travel(12)->hours();
        $current = $this->storedFile('current.pdf');
        $this->travel(12)->hours();

        $publisher = Mockery::mock(DeletionNotificationPublisher::class);
        $publisher->shouldReceive('publish')->once()->withArgs(fn (StoredFile $file, string $source) => $file->id === $expired->id && $source === 'expiration');
        $this->app->instance(DeletionNotificationPublisher::class, $publisher);

        $this->artisan('files:delete-expired')->assertSuccessful();

        $this->assertDatabaseMissing('stored_files', ['id' => $expired->id]);
        Storage::disk($expired->disk)->assertMissing($expired->path);
        $this->assertDatabaseHas('stored_files', ['id' => $current->id]);
        Storage::disk($current->disk)->assertExists($current->path);
    }

    public function test_failed_publication_is_retried_with_the_original_deletion_event(): void
    {
        $file = $this->storedFile('pending.pdf');
        $this->travel(24)->hours();
        $deletedAt = now();

        $publisher = Mockery::mock(DeletionNotificationPublisher::class);
        $publisher->shouldReceive('publish')->once()->andThrow(new DeletionNotificationPublicationException('Broker unavailable'));
        $this->app->instance(DeletionNotificationPublisher::class, $publisher);

        $this->artisan('files:delete-expired')->assertFailed();
        Storage::disk($file->disk)->assertMissing($file->path);
        $this->assertDatabaseHas('stored_files', ['id' => $file->id, 'deletion_source' => 'expiration']);
        $this->assertSame($deletedAt->getTimestamp(), $file->fresh()->deleted_at->getTimestamp());

        $this->travel(5)->minutes();
        $retryPublisher = Mockery::mock(DeletionNotificationPublisher::class);
        $retryPublisher->shouldReceive('publish')->once()->withArgs(function (StoredFile $published, string $source, DateTimeInterface $at) use ($file, $deletedAt): bool {
            return $published->id === $file->id && $source === 'expiration' && $at->getTimestamp() === $deletedAt->getTimestamp();
        });
        $this->app->instance(DeletionNotificationPublisher::class, $retryPublisher);

        $this->artisan('files:delete-expired')->assertSuccessful();
        $this->assertDatabaseMissing('stored_files', ['id' => $file->id]);
        $this->artisan('files:delete-expired')->assertSuccessful();
    }

    public function test_one_failed_file_does_not_prevent_later_files_from_being_deleted(): void
    {
        $first = $this->storedFile('first.pdf');
        $second = $this->storedFile('second.pdf');
        $this->travel(24)->hours();

        $publisher = Mockery::mock(DeletionNotificationPublisher::class);
        $publisher->shouldReceive('publish')->twice()->andReturnUsing(function (StoredFile $file) use ($first): void {
            if ($file->id === $first->id) {
                throw new DeletionNotificationPublicationException('Broker unavailable');
            }
        });
        $this->app->instance(DeletionNotificationPublisher::class, $publisher);

        $this->artisan('files:delete-expired')->assertFailed();

        $this->assertDatabaseHas('stored_files', ['id' => $first->id, 'deletion_source' => 'expiration']);
        $this->assertDatabaseMissing('stored_files', ['id' => $second->id]);
        Storage::disk($first->disk)->assertMissing($first->path);
        Storage::disk($second->disk)->assertMissing($second->path);
    }

    public function test_pending_manual_notification_is_retried_before_expiration(): void
    {
        $file = $this->storedFile('manual.pdf');
        $deletedAt = now();
        $file->forceFill(['deleted_at' => $deletedAt, 'deletion_source' => 'manual'])->save();
        Storage::disk($file->disk)->delete($file->path);

        $publisher = Mockery::mock(DeletionNotificationPublisher::class);
        $publisher->shouldReceive('publish')->once()->withArgs(function (StoredFile $published, string $source, DateTimeInterface $at) use ($file, $deletedAt): bool {
            return $published->id === $file->id && $source === 'manual' && $at->getTimestamp() === $deletedAt->getTimestamp();
        });
        $this->app->instance(DeletionNotificationPublisher::class, $publisher);

        $this->artisan('files:delete-expired')->assertSuccessful();
        $this->assertDatabaseMissing('stored_files', ['id' => $file->id]);
    }

    public function test_all_records_are_processed_when_a_full_batch_is_deleted(): void
    {
        for ($index = 0; $index < 101; $index++) {
            $this->storedFile("batch-{$index}.pdf");
        }
        $this->travel(24)->hours();

        $publisher = Mockery::mock(DeletionNotificationPublisher::class);
        $publisher->shouldReceive('publish')->times(101);
        $this->app->instance(DeletionNotificationPublisher::class, $publisher);

        $this->artisan('files:delete-expired')->assertSuccessful();

        $this->assertDatabaseCount('stored_files', 0);
    }

    private function storedFile(string $name): StoredFile
    {
        $disk = config('filesystems.uploads_disk');
        $path = "documents/{$name}";
        Storage::disk($disk)->put($path, '%PDF-1.4');

        return StoredFile::create([
            'original_name' => $name,
            'disk' => $disk,
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 8,
        ]);
    }
}
