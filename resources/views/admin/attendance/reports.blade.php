<x-admin>
    <x-slot:title>
        Attendance Reports | Team Tasker
    </x-slot:title>

    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Attendance Reports</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Generate and export attendance reports.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Individual Employee Report -->
        <div class="col-md-6">
            <div class="glass-card p-4 h-100">
                <h5 class="text-high fw-semibold mb-3">Individual Employee Report</h5>
                <form action="{{ route('admin.attendance.reports') }}" method="GET">
                    <input type="hidden" name="export" value="single">
                    <div class="mb-3">
                        <label class="form-label text-high fw-semibold">Month</label>
                        <input type="month" name="month" class="form-premium-control w-100" value="{{ \Carbon\Carbon::today()->format('Y-m') }}" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-high fw-semibold">Employee</label>
                        <x-select name="user_id" :options="$users->pluck('name', 'id')->toArray()" placeholder="Select Employee..." class="w-100" required />
                    </div>
                    <button type="submit" class="btn-premium btn-premium-primary w-100 d-flex align-items-center justify-content-center gap-2">
                        <i class="fas fa-download"></i> Download CSV
                    </button>
                </form>
            </div>
        </div>

        <!-- All Users Monthly Summary Report -->
        <div class="col-md-6">
            <div class="glass-card p-4 h-100">
                <h5 class="text-high fw-semibold mb-3">Monthly Summary (All Employees)</h5>
                <form action="{{ route('admin.attendance.reports') }}" method="GET">
                    <input type="hidden" name="export" value="all">
                    <div class="mb-4">
                        <label class="form-label text-high fw-semibold">Month</label>
                        <input type="month" name="month" class="form-premium-control w-100" value="{{ \Carbon\Carbon::today()->format('Y-m') }}" required>
                        <small class="text-low d-block mt-2">Export a summary of total days present, late, absent, etc., for all employees.</small>
                    </div>
                    <button type="submit" class="btn-premium btn-premium-primary w-100 d-flex align-items-center justify-content-center gap-2">
                        <i class="fas fa-download"></i> Download Summary CSV
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-admin>
