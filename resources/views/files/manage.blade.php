<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f7f8f5">
    <title>Manage files — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        <header class="site-header">
            <div class="container-xl header-inner">
                <a class="brand" href="{{ route('files.index') }}" aria-label="File Vault home">
                    <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><path d="M8 6.5h10l6 6V25.5H8V6.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M18 6.5v6h6M12 18h8M12 22h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span>file<span class="brand-accent">vault</span></span>
                </a>
                <nav aria-label="Main navigation"><a class="btn btn-outline-success" href="{{ route('files.index') }}">Upload a file</a></nav>
            </div>
        </header>

        <main class="container-xl page-content management-page">
            <div class="page-intro">
                <div>
                    <span class="eyebrow">YOUR DOCUMENTS</span>
                    <h1>Manage files<span class="heading-period">.</span></h1>
                    <p class="intro-copy">Review your uploaded files and delete any you no longer need.</p>
                </div>
                <div class="retention-pill"><span class="retention-icon" aria-hidden="true">◷</span> Files expire after 24 hours</div>
            </div>

            <section aria-labelledby="files-heading" class="files-section">
                <div class="files-heading-row"><div><span class="section-kicker">STORED DOCUMENTS</span><h2 id="files-heading">Stored files</h2></div><span class="files-caption">Your recent uploads, all in one place</span></div>
                <div id="list-status" class="d-none mb-3" role="status" aria-live="polite" aria-atomic="true"></div>
                <div id="file-list" data-list-url="{{ route('files.list') }}" aria-live="polite" aria-busy="false">@include('files.partials.list', ['files' => $files])</div>
            </section>
        </main>
        <div class="modal fade" id="delete-file-modal" tabindex="-1" aria-labelledby="delete-file-title" aria-describedby="delete-file-description">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content delete-modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="delete-file-title">Delete this file?</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="delete-file-description">
                        <p class="mb-2">This will permanently remove the file from your vault.</p>
                        <p class="delete-file-name mb-0" id="delete-file-name"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirm-delete" data-bs-dismiss="modal">Delete file</button>
                    </div>
                </div>
            </div>
        </div>
        <footer class="site-footer"><div class="container-xl">FILE VAULT <span>·</span> A little less clutter, every day.</div></footer>
    </div>
</body>
</html>
