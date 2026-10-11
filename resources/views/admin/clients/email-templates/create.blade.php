<x-admin title="Create Email Template">
    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Create Email Template</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Design a reusable email template with dynamic variables.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.clients.email-templates.index') }}" class="btn-premium btn-premium-secondary px-4 py-2">Cancel</a>
            <button type="submit" form="templateForm" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
                <i class="fas fa-save me-1"></i> Save Template
            </button>
        </div>
    </div>

    <div class="row g-4">
        {{-- Form --}}
        <div class="col-lg-7">
            <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                <form id="templateForm" action="{{ route('admin.clients.email-templates.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="heading-label d-block mb-2 text-high">Template Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-premium-control w-100" required value="{{ old('name') }}" placeholder="e.g. Welcome Email">
                    </div>

                    <div class="mb-4">
                        <label class="heading-label d-block mb-2 text-high">Email Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" id="subjectInput" class="form-premium-control w-100" required value="{{ old('subject') }}" placeholder="e.g. Welcome to @{{company_name}}!">
                    </div>

                    <div class="mb-4">
                        <label class="heading-label d-block mb-2 text-high">Email Body <span class="text-danger">*</span></label>
                        <textarea name="body" id="bodyEditor" class="rich-editor" rows="12" required>{{ old('body') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="heading-label d-block mb-2 text-high">Status <span class="text-danger">*</span></label>
                        <x-select name="status" required>
                            <option value="active" selected class="bg-dark">Active</option>
                            <option value="inactive" class="bg-dark">Inactive</option>
                        </x-select>
                    </div>
                </form>
            </div>
        </div>

        {{-- Preview & Variables --}}
        <div class="col-lg-5">
            {{-- Variables Reference --}}
            <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                <h6 class="text-high fw-semibold mb-3"><i class="fas fa-code me-2 text-low"></i>Template Variables</h6>
                <p class="text-low mb-3" style="font-size: 0.8rem;">Click a variable to insert it at the cursor position.</p>
                <div class="d-flex flex-wrap gap-2">
                    @php
                        $templateVars = ['{{client_name}}', '{{company_name}}', '{{client_email}}', '{{client_type}}', '{{client_status}}', '{{current_date}}', '{{sender_name}}'];
                    @endphp
                    @foreach($templateVars as $var)
                        <button type="button" class="border-0 px-3 py-1" style="background: rgba(var(--primary-rgb), 0.1); color: var(--primary); border-radius: var(--radius-sm); font-size: 0.75rem; font-family: monospace; cursor: pointer; transition: all 0.2s;"
                            onclick="insertVariable('{{ $var }}')" onmouseover="this.style.background='rgba(var(--primary-rgb), 0.2)'" onmouseout="this.style.background='rgba(var(--primary-rgb), 0.1)'">
                            {{ $var }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Live Preview --}}
            <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-high fw-semibold mb-0"><i class="fas fa-eye me-2 text-low"></i>Live Preview</h6>
                    <button type="button" class="btn-premium btn-premium-secondary px-3 py-1" style="font-size: 0.75rem;" onclick="refreshPreview()">
                        <i class="fas fa-sync-alt me-1"></i> Refresh
                    </button>
                </div>
                <div id="previewArea" style="padding: 16px; background: var(--bg-input); border-radius: var(--radius-md); border: 1px solid var(--border-main); min-height: 200px;">
                    <div class="text-low text-center py-5" style="font-size: 0.85rem;">
                        <i class="fas fa-envelope-open me-2"></i> Write your template to see a preview
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function insertVariable(variable) {
            const editor = tinymce.get('bodyEditor');
            if (editor) {
                editor.execCommand('mceInsertContent', false, variable);
            } else {
                const textarea = document.getElementById('bodyEditor');
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                textarea.value = textarea.value.substring(0, start) + variable + textarea.value.substring(end);
                textarea.focus();
                textarea.setSelectionRange(start + variable.length, start + variable.length);
            }
        }

        function refreshPreview() {
            const subject = document.getElementById('subjectInput').value;
            const editor = tinymce.get('bodyEditor');
            const body = editor ? editor.getContent() : document.getElementById('bodyEditor').value;

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
                const area = document.getElementById('previewArea');
                area.innerHTML = `
                    <div class="mb-3" style="padding-bottom: 12px; border-bottom: 1px solid var(--border-main);">
                        <div class="text-low" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Subject</div>
                        <div class="text-high fw-medium">${data.subject}</div>
                    </div>
                    <div class="text-high" style="font-size: 0.85rem; line-height: 1.6;">${data.body}</div>
                    <div class="mt-3 pt-2" style="border-top: 1px solid var(--border-main);">
                        <div class="text-low" style="font-size: 0.7rem;">Preview client: <span class="text-high">${data.client_name}</span> (${data.client_email})</div>
                    </div>
                `;
            })
            .catch(() => {
                document.getElementById('previewArea').innerHTML = '<div class="text-danger text-center py-3">Preview failed.</div>';
            });
        }
    </script>
</x-admin>
