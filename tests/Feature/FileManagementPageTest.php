<?php

namespace Tests\Feature;

use App\Models\StoredFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileManagementPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_shows_upload_form_and_empty_state(): void
    {
        $this->get(route('files.index'))
            ->assertOk()
            ->assertSee('File Management')
            ->assertSee('name="file"', false)
            ->assertSee('No files have been uploaded yet.');
    }

    public function test_page_and_refresh_endpoint_show_saved_metadata_safely(): void
    {
        $older = $this->file('older.pdf', 1024);
        $this->travel(1)->minutes();
        $newer = $this->file('<report>.docx', 2048);

        foreach ([route('files.index'), route('files.list')] as $url) {
            $response = $this->get($url)->assertOk()
                ->assertSee('&lt;report&gt;.docx', false)
                ->assertDontSee('<report>.docx', false)
                ->assertSee('older.pdf')
                ->assertSee('2.0 KB')
                ->assertSee('Delete');

            $this->assertTrue(strpos($response->getContent(), '&lt;report&gt;.docx') < strpos($response->getContent(), 'older.pdf'));
        }

        $this->assertDatabaseHas('stored_files', ['id' => $older->id]);
        $this->assertDatabaseHas('stored_files', ['id' => $newer->id]);
    }

    private function file(string $name, int $size): StoredFile
    {
        return StoredFile::create([
            'original_name' => $name,
            'disk' => config('filesystems.uploads_disk'),
            'path' => 'documents/example.pdf',
            'mime_type' => 'application/pdf',
            'size' => $size,
        ]);
    }
}
