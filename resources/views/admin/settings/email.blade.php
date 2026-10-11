<x-admin title="Email Integration Settings">
    <div class="top-bar-premium mb-4">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Email Integration Settings</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Configure inbound IMAP and outbound SMTP connections.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" form="emailSettingsForm" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
                Save Changes
            </button>
        </div>
    </div>

    @if (!function_exists('imap_open'))
        <div class="alert alert-warning shadow-premium mb-4"
            style="border-radius: 12px; border: none; background: rgba(255, 193, 7, 0.1); color: #ffc107;">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
                <div>
                    <h6 class="fw-bold mb-1">IMAP Extension Missing</h6>
                    <p class="mb-0 small">The PHP IMAP extension is not enabled on your server. Automatic ticket creation
                        from emails will not work until this extension is installed and enabled.</p>
                </div>
            </div>
        </div>
    @endif

    <form id="emailSettingsForm" action="{{ route('admin.settings.email.store') }}" method="POST">
        @csrf

        @if ($errors->any())
            <div class="alert alert-danger shadow-premium mb-4"
                style="border-radius: 12px; border: none; background: rgba(220, 53, 69, 0.1); color: #ff8585;">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle me-3 fa-lg"></i>
                    <div>
                        <h6 class="fw-bold mb-1">Configuration Error</h6>
                        <ul class="mb-0 small ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
        <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
            <h5 class="fw-bold mb-4" style="color: var(--text-high);">
                <i class="fas fa-inbox me-2" style="color: var(--primary);"></i> Incoming Mail (IMAP)
            </h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="heading-label mb-2">IMAP Host</label>
                    <input type="text" name="imap_host"
                        class="form-premium-control @error('imap_host') is-invalid @enderror"
                        value="{{ old('imap_host', $settings['imap_host'] ?? '') }}" placeholder="imap.gmail.com">
                    @error('imap_host')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="heading-label mb-2">Port</label>
                    <input type="number" name="imap_port" class="form-premium-control"
                        value="{{ $settings['imap_port'] ?? '993' }}" placeholder="993">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="heading-label mb-2">Encryption</label>
                    <x-select name="imap_encryption" placeholder="Select Encryption">
                        <option value="ssl" {{ ($settings['imap_encryption'] ?? '') == 'ssl' ? 'selected' : '' }}
                            class="bg-dark">SSL
                        </option>
                        <option value="tls" {{ ($settings['imap_encryption'] ?? '') == 'tls' ? 'selected' : '' }}
                            class="bg-dark">TLS
                        </option>
                        <option value="null" {{ ($settings['imap_encryption'] ?? '') == 'null' ? 'selected' : '' }}
                            class="bg-dark">
                            None</option>
                    </x-select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="heading-label mb-2">Username</label>
                    <input type="text" name="imap_user" class="form-premium-control"
                        value="{{ $settings['imap_user'] ?? '' }}" placeholder="email@example.com">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="heading-label mb-2">Password</label>
                    <input type="password" name="imap_password" class="form-premium-control"
                        value="{{ $settings['imap_password'] ?? '' }}" placeholder="App Password">
                </div>
            </div>
        </div>

        <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
            <h5 class="fw-bold mb-4" style="color: var(--text-high);">
                <i class="fas fa-paper-plane me-2" style="color: var(--primary);"></i> Outgoing Mail (SMTP)
            </h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="heading-label mb-2">SMTP Host</label>
                    <input type="text" name="smtp_host"
                        class="form-premium-control @error('smtp_host') is-invalid @enderror"
                        value="{{ old('smtp_host', $settings['smtp_host'] ?? '') }}" placeholder="smtp.gmail.com">
                    @error('smtp_host')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3 mb-3">
                    <label class="heading-label mb-2">Port</label>
                    <input type="number" name="smtp_port" class="form-premium-control"
                        value="{{ $settings['smtp_port'] ?? '587' }}" placeholder="587">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="heading-label mb-2">Encryption</label>
                    <x-select name="smtp_encryption" placeholder="Select Encryption">
                        <option value="tls" {{ ($settings['smtp_encryption'] ?? '') == 'tls' ? 'selected' : '' }}
                            class="bg-dark">
                            TLS</option>
                        <option value="ssl" {{ ($settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : '' }}
                            class="bg-dark">
                            SSL</option>
                        <option value="null" {{ ($settings['smtp_encryption'] ?? '') == 'null' ? 'selected' : '' }}
                            class="bg-dark">
                            None</option>
                    </x-select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="heading-label mb-2">Username</label>
                    <input type="text" name="smtp_user" class="form-premium-control"
                        value="{{ $settings['smtp_user'] ?? '' }}" placeholder="email@example.com">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="heading-label mb-2">Password</label>
                    <input type="password" name="smtp_password" class="form-premium-control"
                        value="{{ $settings['smtp_password'] ?? '' }}" placeholder="App Password">
                </div>
            </div>
        </div>

        <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
            <h5 class="fw-bold mb-4" style="color: var(--text-high);">Sender Identity</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="heading-label mb-2">From Name</label>
                    <input type="text" name="from_name" class="form-premium-control"
                        value="{{ $settings['from_name'] ?? config('app.name') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="heading-label mb-2">From Email</label>
                    <input type="email" name="from_email" class="form-premium-control"
                        value="{{ $settings['from_email'] ?? '' }}">
                </div>
            </div>
        </div>

        </div>
    </form>
</x-admin>
