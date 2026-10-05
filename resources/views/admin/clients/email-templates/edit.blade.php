<x-admin title="Edit Email Template">
    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Edit Email Template</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Update template: <span class="text-high fw-medium">{{ $template->name }}</span></p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.clients.email-templates.index') }}" class="btn-premium btn-premium-secondary px-4 py-2">Cancel</a>
            <button type="submit" form="templateForm" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
                <i class="fas fa-save me-1"></i> Update Template
            </button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                <form id="templateForm" action="{{ route('admin.clients.email-templates.update', $template->id) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="heading-label d-block mb-2 text-high">Template Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-premium-control w-100" required value="{{ old('name', $template->name) }}">
                    </div>

                    <div class="mb-4">
                        <label class="heading-label d-block mb-2 text-high">Email Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" id="subjectInput" class="form-premium-control w-100" required value="{{ old('subject', $template->subject) }}">
                    </div>

                    <div class="mb-4">
                        <label class="heading-label d-block mb-2 text-high">Email Body <span class="text-danger">*</span></label>
                        <textarea name="body" id="bodyEditor" class="rich-editor" rows="12" required>{{ old('body', $template->body) }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="heading-label d-block mb-2 text-high">Status <span class="text-danger">*</span></label>
                        <x-select name="status" required>
                            <option value="active" {{ $template->status === 'active' ? 'selected' : '' }} class="bg-dark">Active</option>
                            <option value="inactive" {{ $template->status === 'inactive' ? 'selected' : '' }} class="bg-dark">Inactive</option>
                        </x-select>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                <h6 class="text-high fw-semibold mb-3"><i class="fas fa-code me-2 text-low"></i>Template Variables</h6>
                <div class="d-flex flex-wrap gap-2">
                    @php
                        $templateVars = ['{{client_name}}', '{{company_name}}', '{{client_email}}', '{{client_type}}', '{{client_status}}', '{{current_date}}', '{{sender_name}}'];
                    @endphp
                    @foreach($templateVars as $var)
                        <button type="button" class="border-0 px-3 py-1" style="background: rgba(var(--primary-rgb), 0.1); color: var(--primary); border-radius: var(--radius-sm); font-size: 0.75rem; font-family: monospace; cursor: pointer;"
                            onclick="insertVariable('{{ $var }}')">
                            {{ $var }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-high fw-semibold mb-0"><i class="fas fa-eye me-2 text-low"></i>Live Preview</h6>
                    <button type="button" class="btn-premium btn-premium-secondary px-3 py-1" style="font-size: 0.75rem;" onclick="refreshPreview()">
                        <i class="fas fa-sync-alt me-1"></i> Refresh
                    </button>
                </div>
                <div id="previewArea" style="padding: 16px; background: var(--bg-input); border-radius: var(--radius-md); border: 1px solid var(--border-main); min-height: 200px;">
                    <div class="text-low text-center py-5" style="font-size: 0.85rem;">
                        <i class="fas fa-envelope-open me-2"></i> Click "Refresh" to preview
                    </div>
                </div>
            </div>

            {{-- Template Meta --}}
            <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                <h6 class="text-high fw-semibold mb-3"><i class="fas fa-info-circle me-2 text-low"></i>Template Info</h6>
                <div class="text-low" style="font-size: 0.8rem;">
                    <div class="mb-2"><strong class="text-high">Created:</strong> {{ $template->created_at->format('d M Y H:i') }}</div>
                    <div class="mb-2"><strong class="text-high">Updated:</strong> {{ $template->updated_at->format('d M Y H:i') }}</div>
                    <div><strong class="text-high">Emails Sent:</strong> {{ $template->emails()->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function insertVariable(variable) {
            const editor = tinymce.get('bodyEditor');
            if (editor) {
                editor.execCommand('mceInsertContent', false, variable);
            }
        }

        function refreshPreview() {
            const subject = document.getElementById('subjectInput').value;
            const editor = tinymce.get('bodyEditor');
            const body = editor ? editor.getContent() : '';

            fetch('{{ route("admin.clients.email-templates.preview") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ subject, body })
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('previewArea').innerHTML = `
                    <div class="mb-3" style="padding-bottom: 12px; border-bottom: 1px solid var(--border-main);">
                        <div class="text-low" style="font-size: 0.7rem; text-transform: uppercase;">Subject</div>
                        <div class="text-high fw-medium">${data.subject}</div>
                    </div>
                    <div class="text-high" style="font-size: 0.85rem; line-height: 1.6;">${data.body}</div>
                `;
            });
        }
    </script>
</x-admin>
