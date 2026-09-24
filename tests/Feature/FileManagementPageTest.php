<?php

namespace Tests\Feature;

use App\Models\StoredFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileManagementPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_page_links_to_a_separate_empty_management_page(): void
    {
        $this->get(route('files.index'))
            ->assertOk()
            ->assertSee('Upload a file')
            ->assertSee('name="file"', false)
            ->assertSee('href="'.route('files.manage').'"', false)
            ->assertDontSee('id="file-list"', false);

        $this->get(route('files.manage'))
            ->assertOk()
            ->assertSee('Manage files')
            ->assertSee('No files have been uploaded yet.')
            ->assertSee('id="delete-file-modal"', false)
            ->assertSee('id="confirm-delete"', false)
            ->assertSee('href="'.route('files.index').'"', false)
            ->assertDontSee('id="upload-form"', false);
    }

    public function test_page_and_refresh_endpoint_show_saved_metadata_safely(): void
    {
        $older = $this->file('older.pdf', 1024);
        $this->travel(1)->minutes();
        $newer = $this->file('<report>.docx', 2048);

        $this->get(route('files.index'))->assertDontSee('older.pdf');

        foreach ([route('files.manage'), route('files.list')] as $url) {
            $response = $this->get($url)->assertOk()
                ->assertSee('&lt;report&gt;.docx', false)
                ->assertDontSee('<report>.docx', false)
                ->assertSee('older.pdf')
                ->assertSee('2.0 KB')
                ->assertSee('Delete')
                ->assertSee('data-delete-url="'.route('files.destroy', $newer).'"', false);

            if ($url === route('files.manage')) {
                $response->assertSee('data-bs-target="#delete-file-modal"', false)
                    ->assertSee('data-file-name="&lt;report&gt;.docx"', false);
            }

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
