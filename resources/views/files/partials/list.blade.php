@if ($files->isEmpty())
    <div class="empty-state">
        <span class="empty-icon" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none"><path d="M7 15h34v24H7V15ZM13 15V9h22v6M19 25h10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
        <h3>Your vault is ready</h3>
        <p>No files have been uploaded yet. <a href="{{ route('files.index') }}">Upload a document</a> to get started.</p>
    </div>
@else
    <div class="file-table-wrap table-responsive">
        <table class="table file-table align-middle mb-0">
            <caption class="visually-hidden">List of stored files</caption>
            <thead><tr><th scope="col">FILE NAME</th><th scope="col">TYPE</th><th scope="col">SIZE</th><th scope="col">UPLOADED</th><th scope="col">EXPIRES</th><th scope="col"><span class="visually-hidden">Action</span></th></tr></thead>
            <tbody>
                @foreach ($files as $file)
                    <tr>
                        <th scope="row" data-label="File name"><span class="file-name-cell"><span class="file-type-icon" aria-hidden="true">{{ strtoupper(pathinfo($file->original_name, PATHINFO_EXTENSION)) === 'PDF' ? 'P' : 'W' }}</span><span class="file-name text-break">{{ $file->original_name }}</span></span></th>
                        <td data-label="Type"><span class="type-badge">{{ strtoupper(pathinfo($file->original_name, PATHINFO_EXTENSION)) }}</span></td>
                        <td data-label="Size">{{ number_format($file->size / 1024, 1) }} KB</td>
                        <td data-label="Uploaded"><time datetime="{{ $file->uploaded_at->toIso8601String() }}">{{ $file->uploaded_at->format('d.m.Y H:i') }}</time></td>
                        <td data-label="Expires"><time datetime="{{ $file->expires_at->toIso8601String() }}">{{ $file->expires_at->format('d.m.Y H:i') }}</time></td>
                        <td data-label="Action" class="action-cell"><button type="button" class="btn btn-outline-danger btn-sm delete-file" data-bs-toggle="modal" data-bs-target="#delete-file-modal" data-delete-url="{{ route('files.destroy', $file) }}" data-file-name="{{ $file->original_name }}" aria-label="Delete {{ $file->original_name }}">Delete</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
