<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f7f8f5">
    <title>Upload a file — {{ config('app.name') }}</title>
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
                <nav aria-label="Main navigation"><a class="btn btn-outline-success" href="{{ route('files.manage') }}">Manage files</a></nav>
            </div>
        </header>

        <main class="container-xl page-content">
            <div class="page-intro">
                <div>
                    <span class="eyebrow">YOUR WORKSPACE</span>
                    <h1>Upload a file<span class="heading-period">.</span></h1>
                    <p class="intro-copy">Add a document to your temporary vault. You can review and delete it on the files page.</p>
                </div>
                <div class="retention-pill"><span class="retention-icon" aria-hidden="true">◷</span> Files expire after 24 hours</div>
            </div>

            <div class="workspace-grid">
                <section aria-labelledby="upload-heading" class="panel upload-panel">
                    <div class="panel-heading">
                        <div><span class="section-kicker">01 / ADD A DOCUMENT</span><h2 id="upload-heading">Upload a file</h2><p>Choose a PDF or Word document to add to your vault.</p></div>
                    </div>
                    <form id="upload-form" action="{{ route('files.store') }}" method="post" enctype="multipart/form-data" data-manage-url="{{ route('files.manage') }}">
                        @csrf
                        <div id="drop-zone" class="drop-zone">
                            <span class="upload-illustration" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none"><path d="M13 7h15l8 8v26H13V7Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M28 7v8h8M18 28l6-6 6 6M24 22v13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                            <label for="file" class="drop-title">Drop your file here <span>or browse</span></label>
                            <p id="file-help" class="drop-help">PDF or DOCX · Maximum 10 MB</p>
                            <input id="file" name="file" type="file" class="file-input" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" aria-describedby="file-help file-error selected-file" required>
                            <p id="selected-file" class="selected-file d-none" aria-live="polite"></p>
                        </div>
                        <div id="file-error" class="field-error" role="alert"></div>
                        <button id="upload-button" type="submit" class="btn btn-primary upload-button"><span>Upload document</span><span aria-hidden="true">↗</span></button>
                    </form>
                    <div id="upload-status" class="mt-3 d-none" role="status" aria-live="polite" aria-atomic="true"></div>
                </section>

                <aside class="info-panel" aria-label="How file storage works">
                    <div class="info-panel-top"><span class="section-kicker">GOOD TO KNOW</span><h2>Made for files in motion.</h2><p>Keep the documents you need for today without building up a permanent archive.</p></div>
                    <div class="info-steps">
                        <div class="info-step"><span class="step-number">01</span><div><strong>Upload securely</strong><span>Choose a PDF or DOCX up to 10 MB.</span></div></div>
                        <div class="info-step"><span class="step-number">02</span><div><strong>Stay in control</strong><span>View your files and delete them whenever you need.</span></div></div>
                        <div class="info-step"><span class="step-number">03</span><div><strong>Automatic cleanup</strong><span>Files are removed 24 hours after upload.</span></div></div>
                    </div>
                </aside>
            </div>

        </main>
        <footer class="site-footer"><div class="container-xl">FILE VAULT <span>·</span> A little less clutter, every day.</div></footer>
    </div>
</body>
</html>
