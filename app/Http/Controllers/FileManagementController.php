<?php

namespace App\Http\Controllers;

use App\Models\StoredFile;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;

class FileManagementController extends Controller
{
    public function index(): View
    {
        return view('files.index');
    }

    public function manage(): View
    {
        return view('files.manage', ['files' => $this->files()]);
    }

    public function list(): View
    {
        return view('files.partials.list', ['files' => $this->files()]);
    }

    private function files(): Collection
    {
        return StoredFile::query()->orderByDesc('uploaded_at')->orderByDesc('id')->get();
    }
}
