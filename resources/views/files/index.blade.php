<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>File Management — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="container py-5">
        <div class="mb-4">
            <h1 class="h2 mb-2">File Management</h1>
            <p class="text-secondary mb-0">Documents are stored for 24 hours after upload.</p>
        </div>

        <section aria-labelledby="upload-heading" class="card mb-4">
            <div class="card-body">
                <h2 id="upload-heading" class="h5 card-title">Upload a file</h2>
                <form id="upload-form" action="{{ route('files.store') }}" method="post" enctype="multipart/form-data" data-list-url="{{ route('files.list') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="file" class="form-label">PDF or DOCX file</label>
                        <input id="file" name="file" type="file" class="form-control" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" aria-describedby="file-help file-error" required>
                        <div id="file-help" class="form-text">Maximum size: 10 MB. The server performs the final validation.</div>
                        <div id="file-error" class="invalid-feedback" role="alert"></div>
                    </div>
                    <button id="upload-button" type="submit" class="btn btn-primary">Upload</button>
                </form>
                <div id="upload-status" class="mt-3 d-none" role="status" aria-live="polite" aria-atomic="true"></div>
            </div>
        </section>

        <section aria-labelledby="files-heading">
            <h2 id="files-heading" class="h5 mb-3">Stored files</h2>
            <div id="list-status" class="d-none mb-3" role="status" aria-live="polite" aria-atomic="true"></div>
            <div id="file-list" aria-live="polite" aria-busy="false">
                @include('files.partials.list', ['files' => $files])
            </div>
        </section>
    </main>
</body>
</html>
