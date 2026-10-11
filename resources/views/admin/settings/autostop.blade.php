<x-admin title="Auto Stop Timers">
    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Auto Stop Timers</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Configure automatic timer stopping at the end of the day.</p>
        </div>
        <div class="d-flex gap-3">
            <button type="submit" form="autostopForm" class="btn-premium btn-premium-primary px-4 py-2 shadow-sm">
                <i class="fas fa-save me-2"></i> Save Configuration
            </button>
        </div>
    </div>

    <div class="card-premium mt-3">
        @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        <form id="autostopForm" action="{{ route('admin.settings.autostop.store') }}" method="POST">
            @csrf
            
            <div class="mb-4">
                <label class="heading-label mb-2">Enable Auto Stop</label>
                <select name="auto_stop_timers" id="auto_stop_timers" class="form-select form-premium-control" onchange="toggleTimeInput(this.value)">
                    <option value="no" {{ isset($settings['auto_stop_timers']) && $settings['auto_stop_timers'] == 'no' ? 'selected' : '' }}>No, leave timers running</option>
                    <option value="yes" {{ isset($settings['auto_stop_timers']) && $settings['auto_stop_timers'] == 'yes' ? 'selected' : '' }}>Yes, stop timers automatically</option>
                </select>
                <div class="text-low small mt-2">If enabled, all active task timers will be forcefully stopped at the designated time.</div>
            </div>

            <div class="mb-4" id="office_close_time_container" style="display: {{ isset($settings['auto_stop_timers']) && $settings['auto_stop_timers'] == 'yes' ? 'block' : 'none' }};">
                <label class="heading-label mb-2">Office Close Time</label>
                <input type="time" name="office_close_time" class="form-premium-control"
                    value="{{ $settings['office_close_time'] ?? '18:00' }}">
                <div class="text-low small mt-2">The system runs a scheduler every minute to check if this time has been reached.</div>
            </div>
        </form>
    </div>

    <script>
        function toggleTimeInput(val) {
            if (val === 'yes') {
                document.getElementById('office_close_time_container').style.display = 'block';
            } else {
                document.getElementById('office_close_time_container').style.display = 'none';
            }
        }
    </script>
</x-admin>
