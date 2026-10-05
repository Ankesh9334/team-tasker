<x-admin title="Compose Email to Clients">
    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Compose Email</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Send personalized emails to one or many clients.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.clients.emails.queue') }}" class="btn-premium btn-premium-secondary px-4 py-2">
                <i class="fas fa-inbox me-1"></i> Email Queue
            </a>
        </div>
    </div>

    <form action="{{ route('admin.clients.emails.send') }}" method="POST" enctype="multipart/form-data" id="composeForm">
        @csrf

        <div class="row g-4">
            {{-- Left: Compose Form --}}
            <div class="col-lg-8">
                <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">

                    {{-- Recipients Selection --}}
                    <div class="mb-4">
                        <label class="heading-label d-block mb-2 text-high">
                            <i class="fas fa-users me-1 text-low"></i> Recipients <span class="text-danger">*</span>
                        </label>
                        <select name="client_ids[]" id="clientSelect" multiple class="form-premium-control w-100">
                            @foreach($allClients as $client)
                                <option value="{{ $client->id }}"
                                    {{ is_array($clientIds) && in_array($client->id, $clientIds) ? 'selected' : '' }}>
                                    {{ $client->name }} {{ $client->company_name ? '(' . $client->company_name . ')' : '' }} — {{ $client->email }}
                                </option>
                            @endforeach
                        </select>
                        <div class="text-low mt-2" style="font-size: 0.75rem;">
                            <i class="fas fa-info-circle me-1"></i> Select individual clients, or use the quick filter buttons below.
                        </div>
                    </div>

                    {{-- Quick Bulk Filters --}}
                    <div class="mb-4 d-flex flex-wrap gap-2">
                        @foreach($clientTypes as $type)
                            <button type="button" class="border-0 px-3 py-1" style="background: rgba(var(--primary-rgb), 0.08); color: var(--primary); border-radius: 20px; font-size: 0.75rem; cursor: pointer; transition: all 0.2s;"
                                onclick="selectByType('{{ $type }}')" onmouseover="this.style.background='rgba(var(--primary-rgb), 0.15)'" onmouseout="this.style.background='rgba(var(--primary-rgb), 0.08)'">
                                {{ $type }}
                            </button>
                        @endforeach
                        <button type="button" class="border-0 px-3 py-1" style="background: rgba(16,185,129,0.08); color: #10b981; border-radius: 20px; font-size: 0.75rem; cursor: pointer;"
                            onclick="selectByStatus('active')">Active Only</button>
                        <button type="button" class="border-0 px-3 py-1" style="background: rgba(245,158,11,0.08); color: #f59e0b; border-radius: 20px; font-size: 0.75rem; cursor: pointer;"
                            onclick="selectByStatus('prospect')">Prospects</button>
                        <button type="button" class="border-0 px-3 py-1" style="background: rgba(100,116,139,0.1); color: #64748b; border-radius: 20px; font-size: 0.75rem; cursor: pointer;"
                            onclick="selectAll()">Select All</button>
                        <button type="button" class="border-0 px-3 py-1" style="background: rgba(239,68,68,0.08); color: #ef4444; border-radius: 20px; font-size: 0.75rem; cursor: pointer;"
                            onclick="clearAll()">Clear All</button>
                    </div>

                    {{-- Template Selection --}}
                    <div class="mb-4">
                        <label class="heading-label d-block mb-2 text-high">
                            <i class="fas fa-envelope-open-text me-1 text-low"></i> Use Template (Optional)
                        </label>
                        <select id="templateSelect" class="form-premium-control w-100" onchange="loadTemplate(this.value)">
                            <option value="">— Write Custom Email —</option>
                            @foreach($templates as $tpl)
                                <option value="{{ $tpl->id }}" data-subject="{{ $tpl->subject }}" data-body="{{ e($tpl->body) }}">
                                    {{ $tpl->name }}
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="template_id" id="templateIdInput" value="">
                    </div>

                    {{-- Campaign Name --}}
                    <div class="mb-4">
                        <label class="heading-label d-block mb-2 text-high">Campaign Name (Optional)</label>
                        <input type="text" name="campaign_name" class="form-premium-control w-100" placeholder="e.g. Q4 Outreach" value="{{ old('campaign_name') }}">
                    </div>

                    {{-- Subject --}}
                    <div class="mb-4">
                        <label class="heading-label d-block mb-2 text-high">Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" id="subjectInput" class="form-premium-control w-100" required placeholder="Email subject line..." value="{{ old('subject') }}">
                    </div>

                    {{-- Body --}}
                    <div class="mb-4">
                        <label class="heading-label d-block mb-2 text-high">Email Body <span class="text-danger">*</span></label>
                        <textarea name="body" id="bodyEditor" class="rich-editor" rows="15" required>{{ old('body') }}</textarea>
                    </div>

                    {{-- Attachments --}}
                    <div class="mb-4">
                        <label class="heading-label d-block mb-2 text-high">
                            <i class="fas fa-paperclip me-1 text-low"></i> Attachments (Optional)
                        </label>
                        <input type="file" name="attachments[]" multiple class="form-premium-control w-100" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.gif,.zip">
                        <div class="text-low mt-1" style="font-size: 0.7rem;">Max 10MB per file. Allowed: PDF, DOC, XLS, Images, ZIP.</div>
                    </div>

                    {{-- Submit --}}
                    <div class="mt-4 pt-3" style="border-top: 1px solid var(--border-main);">
                        <button type="submit" class="btn-premium btn-premium-primary px-5 py-2 shadow-sm" id="sendBtn">
                            <i class="fas fa-paper-plane me-2"></i> Send Emails
                        </button>
                    </div>
                </div>
            </div>

            {{-- Right: Recipient Analysis & Variables --}}
            <div class="col-lg-4">
                {{-- Recipient Analysis --}}
                <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                    <h6 class="text-high fw-semibold mb-3"><i class="fas fa-chart-pie me-2 text-low"></i>Recipient Analysis</h6>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-low" style="font-size: 0.8rem;">Total Matching</span>
                            <span class="text-high fw-semibold">{{ $totalMatching }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span style="font-size: 0.8rem; color: #10b981;">Valid Emails</span>
                            <span class="fw-semibold" style="color: #10b981;">{{ $validEmails->count() }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span style="font-size: 0.8rem; color: #ef4444;">Missing/Invalid</span>
                            <span class="fw-semibold" style="color: #ef4444;">{{ $missingEmails->count() }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span style="font-size: 0.8rem; color: #f59e0b;">Opted Out</span>
                            <span class="fw-semibold" style="color: #f59e0b;">{{ $optedOut->count() }}</span>
                        </div>
                    </div>
                    <div style="padding: 10px; background: rgba(var(--primary-rgb), 0.05); border-radius: var(--radius-sm); border: 1px solid rgba(var(--primary-rgb), 0.15);">
                        <div class="text-low" style="font-size: 0.7rem; text-transform: uppercase;">Will Receive Email</div>
                        <div class="fw-bold" style="font-size: 1.3rem; color: var(--primary);">{{ $validEmails->count() }}</div>
                    </div>
                </div>

                {{-- Variable Reference --}}
                <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
                    <h6 class="text-high fw-semibold mb-3"><i class="fas fa-code me-2 text-low"></i>Variables</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach(['{{client_name}}', '{{company_name}}', '{{client_email}}', '{{client_type}}', '{{current_date}}', '{{sender_name}}'] as $var)
                            <button type="button" class="border-0 px-2 py-1" style="background: rgba(var(--primary-rgb), 0.1); color: var(--primary); border-radius: var(--radius-sm); font-size: 0.7rem; font-family: monospace; cursor: pointer;"
                                onclick="insertVariable('{{ $var }}')">{{ $var }}</button>
                        @endforeach
                    </div>
                </div>

                {{-- Send Confirmation --}}
                <div class="data-grid-wrapper p-4 mb-4" style="background: rgba(245,158,11,0.05); border: 1px solid rgba(245,158,11,0.2); border-radius: var(--radius-lg);">
                    <h6 class="fw-semibold mb-2" style="color: #f59e0b;"><i class="fas fa-exclamation-triangle me-2"></i>Before Sending</h6>
                    <ul class="text-low mb-0" style="font-size: 0.8rem; padding-left: 20px;">
                        <li>Emails are queued and sent in background</li>
                        <li>Each client gets a personalized copy</li>
                        <li>Opted-out clients are automatically excluded</li>
                        <li>Track delivery status in Email Queue</li>
                    </ul>
                </div>
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize TomSelect for multi-select
            if (typeof TomSelect !== 'undefined') {
                new TomSelect('#clientSelect', {
                    plugins: ['remove_button'],
                    maxItems: null,
                    placeholder: 'Search and select clients...',
                });
            }
        });

        const clientData = @json($allClients);

        function selectByType(type) {
            const ts = document.getElementById('clientSelect').tomselect;
            if (!ts) return;
            ts.clear();
            clientData.filter(c => c.client_type === type).forEach(c => ts.addItem(c.id));
        }

        function selectByStatus(status) {
            const ts = document.getElementById('clientSelect').tomselect;
            if (!ts) return;
            ts.clear();
            clientData.filter(c => c.status === status).forEach(c => ts.addItem(c.id));
        }

        function selectAll() {
            const ts = document.getElementById('clientSelect').tomselect;
            if (!ts) return;
            clientData.forEach(c => ts.addItem(c.id));
        }

        function clearAll() {
            const ts = document.getElementById('clientSelect').tomselect;
            if (!ts) return;
            ts.clear();
        }

        function loadTemplate(templateId) {
            document.getElementById('templateIdInput').value = templateId;
            if (!templateId) return;

            const opt = document.querySelector(`#templateSelect option[value="${templateId}"]`);
            if (opt) {
                document.getElementById('subjectInput').value = opt.dataset.subject || '';
                const editor = tinymce.get('bodyEditor');
                if (editor) {
                    // Decode HTML entities from data attribute
                    const div = document.createElement('div');
                    div.innerHTML = opt.dataset.body || '';
                    editor.setContent(div.innerHTML);
                }
            }
        }

        function insertVariable(variable) {
            const editor = tinymce.get('bodyEditor');
            if (editor) {
                editor.execCommand('mceInsertContent', false, variable);
            }
        }

        // Confirm before send
        document.getElementById('composeForm').addEventListener('submit', function(e) {
            const ts = document.getElementById('clientSelect').tomselect;
            const count = ts ? ts.items.length : 0;
            if (count === 0) {
                e.preventDefault();
                alert('Please select at least one recipient.');
                return;
            }
            if (!confirm(`Send email to ${count} client(s)?`)) {
                e.preventDefault();
            }
        });
    </script>
</x-admin>
