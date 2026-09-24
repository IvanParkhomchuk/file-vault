@if ($files->isEmpty())
    <p class="text-secondary">No files have been uploaded yet.</p>
@else
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <caption class="visually-hidden">List of stored files</caption>
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Type</th>
                    <th scope="col">Size</th>
                    <th scope="col">Uploaded</th>
                    <th scope="col">Expires</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($files as $file)
                    <tr>
                        <th scope="row" class="text-break">{{ $file->original_name }}</th>
                        <td>{{ strtoupper(pathinfo($file->original_name, PATHINFO_EXTENSION)) }}</td>
                        <td>{{ number_format($file->size / 1024, 1) }} KB</td>
                        <td><time datetime="{{ $file->uploaded_at->toIso8601String() }}">{{ $file->uploaded_at->format('d.m.Y H:i') }}</time></td>
                        <td><time datetime="{{ $file->expires_at->toIso8601String() }}">{{ $file->expires_at->format('d.m.Y H:i') }}</time></td>
                        <td><button type="button" class="btn btn-outline-danger btn-sm delete-file" data-delete-url="{{ route('files.destroy', $file) }}" aria-label="Delete {{ $file->original_name }}">Delete</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
