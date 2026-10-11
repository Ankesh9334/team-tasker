<x-admin title="Import History">
    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Import History</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Track all CSV import jobs and their results.</p>
        </div>
        <a href="{{ route('admin.clients.imports.index') }}" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
            <i class="fas fa-plus-circle me-1"></i> New Import
        </a>
    </div>

    <div class="data-grid-wrapper mb-5">
        <div class="data-grid-top">
            <div class="data-grid-results">{{ $imports->total() }} Results</div>
            <div class="data-grid-actions">{{ $imports->links() }}</div>
        </div>

        <div class="table-responsive">
            <table class="table data-grid-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>FILE NAME</th>
                        <th>IMPORTED BY</th>
                        <th>STATUS</th>
                        <th>TOTAL</th>
                        <th>IMPORTED</th>
                        <th>FAILED</th>
                        <th>SKIPPED</th>
                        <th>CREATED AT</th>
                        <th class="text-end">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($imports as $import)
                        <tr>
                            <td class="text-low" style="color: #64748b !important;">#{{ $import->id }}</td>
                            <td class="text-high fw-medium">
                                <i class="fas fa-file-csv me-1" style="color: var(--primary);"></i>
                                {{ \Illuminate\Support\Str::limit($import->file_name, 30) }}
                            </td>
                            <td class="text-high">{{ $import->user?->name ?? 'System' }}</td>
                            <td>
                                @if($import->status === 'completed')
                                    <span style="background: rgba(16,185,129,0.1); color: #10b981; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">COMPLETED</span>
                                @elseif($import->status === 'processing')
                                    <span style="background: rgba(59,130,246,0.1); color: #3b82f6; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">PROCESSING</span>
                                @elseif($import->status === 'failed')
                                    <span style="background: rgba(239,68,68,0.1); color: #ef4444; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">FAILED</span>
                                @else
                                    <span style="background: rgba(245,158,11,0.1); color: #f59e0b; font-size: 0.65rem; font-weight: 700; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">PENDING</span>
                                @endif
                            </td>
                            <td class="text-high">{{ $import->total_rows ?? '—' }}</td>
                            <td style="color: #10b981;">{{ $import->imported ?? '—' }}</td>
                            <td style="color: #ef4444;">{{ $import->failed ?? '—' }}</td>
                            <td style="color: #f59e0b;">{{ $import->skipped ?? '—' }}</td>
                            <td class="text-high">{{ $import->created_at->format('d M Y H:i') }}</td>
                            <td class="text-end pe-4">
                                @if($import->failed > 0)
                                    <button onclick="viewErrors({{ $import->id }})" class="action-link border-0 bg-transparent" title="View Errors">
                                        <i class="fas fa-exclamation-circle" style="color: #ef4444;"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center py-5 text-medium">No imports found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Error Details Modal --}}
    <div class="modal fade" id="errorsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-main);">
                    <h5 class="modal-title text-high fw-semibold">Import Error Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="errorsBody">
                    <div class="text-center py-4 text-low">Loading...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewErrors(id) {
            const modal = new bootstrap.Modal(document.getElementById('errorsModal'));
            const body = document.getElementById('errorsBody');
            body.innerHTML = '<div class="text-center py-4 text-low"><i class="fas fa-spinner fa-spin me-2"></i>Loading...</div>';
            modal.show();

            fetch(`{{ url('admin/clients/imports') }}/${id}/errors`)
                .then(r => r.json())
                .then(data => {
                    if (!data.errors || data.errors.length === 0) {
                        body.innerHTML = '<div class="text-center py-4 text-low">No error details available.</div>';
                        return;
                    }
                    let html = `<div class="mb-3"><span class="fw-semibold text-high">${data.file_name}</span> — <span class="text-low">${data.failed} failed records</span></div>`;
                    html += '<div class="table-responsive"><table class="table data-grid-table"><thead><tr><th>ROW</th><th>ERROR</th></tr></thead><tbody>';
                    data.errors.forEach(err => {
                        html += `<tr><td class="text-low">${err.row || '—'}</td><td class="text-high" style="font-size: 0.8rem;">${err.message || err.error || JSON.stringify(err)}</td></tr>`;
                    });
                    html += '</tbody></table></div>';
                    body.innerHTML = html;
                })
                .catch(() => {
                    body.innerHTML = '<div class="text-center py-4 text-danger">Failed to load errors.</div>';
                });
        }
    </script>
</x-admin>
