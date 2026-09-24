<?php

namespace Tests\Feature;

use App\Models\StoredFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.uploads_disk'));
    }

    public function test_pdf_upload_stores_private_file_and_metadata_with_24_hour_expiration(): void
    {
        $uploadedAt = Carbon::parse('2026-09-24 12:00:00');
        $this->travelTo($uploadedAt);

        $response = $this->postJson(route('files.store'), [
            'file' => $this->pdf('report.pdf'),
        ]);

        $response->assertCreated()
            ->assertJsonPath('original_name', 'report.pdf')
            ->assertJsonPath('mime_type', 'application/pdf')
            ->assertJsonPath('uploaded_at', $uploadedAt->toIso8601String())
            ->assertJsonPath('expires_at', $uploadedAt->copy()->addDay()->toIso8601String());

        $file = StoredFile::sole();
        $this->assertSame($file->id, $response->json('id'));
        $this->assertSame(config('filesystems.uploads_disk'), $file->disk);
        $this->assertSame('report.pdf', $file->original_name);
        $this->assertSame($file->size, $response->json('size'));
        $this->assertMatchesRegularExpression('~^documents/[0-9a-f-]{36}\.pdf$~', $file->path);
        $this->assertTrue($file->uploaded_at->equalTo($uploadedAt));
        $this->assertTrue($file->expires_at->equalTo($uploadedAt->copy()->addDay()));
        Storage::disk($file->disk)->assertExists($file->path);
        $this->assertStringStartsWith('%PDF-', Storage::disk($file->disk)->get($file->path));
    }

    public function test_docx_upload_is_accepted_and_stored(): void
    {
        $upload = $this->docx('report.docx');

        try {
            $this->postJson(route('files.store'), ['file' => $upload])
                ->assertCreated()
                ->assertJsonPath('original_name', 'report.docx');

            $file = StoredFile::sole();
            $this->assertSame('application/vnd.openxmlformats-officedocument.wordprocessingml.document', $file->mime_type);
            $this->assertMatchesRegularExpression('~^documents/[0-9a-f-]{36}\.docx$~', $file->path);
            Storage::disk($file->disk)->assertExists($file->path);
        } finally {
            @unlink($upload->getPathname());
        }
    }

    public function test_unsupported_content_is_rejected_even_with_a_pdf_extension(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'forged-');
        file_put_contents($path, 'plain text');

        try {
            $this->postJson(route('files.store'), [
                'file' => new UploadedFile($path, 'forged.pdf', null, null, true),
            ])->assertUnprocessable()->assertJsonValidationErrors('file');
        } finally {
            @unlink($path);
        }

        $this->assertDatabaseCount('stored_files', 0);
        $this->assertSame([], Storage::disk(config('filesystems.uploads_disk'))->allFiles());
    }

    public function test_file_larger_than_10_mb_is_rejected(): void
    {
        $this->postJson(route('files.store'), [
            'file' => UploadedFile::fake()->createWithContent('large.pdf', "%PDF-1.4\n".str_repeat(' ', 10 * 1024 * 1024)."\n%%EOF"),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('stored_files', 0);
        $this->assertSame([], Storage::disk(config('filesystems.uploads_disk'))->allFiles());
    }

    public function test_pdf_of_exactly_10_mb_is_accepted(): void
    {
        $content = "%PDF-1.4\n".str_repeat(' ', 10 * 1024 * 1024 - strlen("%PDF-1.4\n%%EOF")).'%%EOF';

        $this->postJson(route('files.store'), [
            'file' => UploadedFile::fake()->createWithContent('limit.pdf', $content),
        ])->assertCreated()->assertJsonPath('size', 10 * 1024 * 1024);

        $file = StoredFile::sole();
        Storage::disk($file->disk)->assertExists($file->path);
    }

    public function test_file_is_removed_when_metadata_cannot_be_persisted(): void
    {
        StoredFile::creating(function (): void {
            throw new RuntimeException('Database write failed');
        });

        $this->postJson(route('files.store'), [
            'file' => $this->pdf('report.pdf'),
        ])->assertInternalServerError();

        $this->assertDatabaseCount('stored_files', 0);
        $this->assertSame([], Storage::disk(config('filesystems.uploads_disk'))->allFiles());
    }

    public function test_upload_rejects_a_public_disk_even_when_selected_by_configuration(): void
    {
        config()->set('filesystems.uploads_disk', 'public');

        $this->postJson(route('files.store'), [
            'file' => $this->pdf('report.pdf'),
        ])->assertInternalServerError();

        $this->assertDatabaseCount('stored_files', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('documents'));
    }

    public function test_upload_rejects_a_private_label_on_a_web_accessible_root(): void
    {
        config()->set('filesystems.disks.exposed_uploads', [
            'driver' => 'local',
            'root' => public_path(),
            'visibility' => 'private',
        ]);
        config()->set('filesystems.uploads_disk', 'exposed_uploads');

        $this->postJson(route('files.store'), [
            'file' => $this->pdf('report.pdf'),
        ])->assertInternalServerError();

        $this->assertDatabaseCount('stored_files', 0);
    }

    public function test_upload_rejects_a_private_disk_exposed_by_a_public_storage_link(): void
    {
        config()->set('filesystems.links', [public_path('documents') => storage_path('app/uploads')]);

        $this->postJson(route('files.store'), [
            'file' => $this->pdf('report.pdf'),
        ])->assertInternalServerError();

        $this->assertDatabaseCount('stored_files', 0);
        $this->assertSame([], Storage::disk(config('filesystems.uploads_disk'))->allFiles('documents'));
    }

    private function pdf(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
    }

    private function docx(string $name): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'docx-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body/></w:document>');
        $zip->close();

        return new UploadedFile($path, $name, null, null, true);
    }
}
