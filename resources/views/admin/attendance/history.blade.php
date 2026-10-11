<x-admin>
    <x-slot:title>
        Attendance History | Team Tasker
    </x-slot:title>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-high">Attendance Audit Trail</h3>
            <p class="text-low mb-0">Record history for {{ $attendance->user->name }} on {{ $attendance->attendance_date?->format('d M Y') ?? $attendance->date }}.</p>
        </div>
        <a href="{{ url()->previous() }}" class="btn-premium btn-premium-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="glass-card p-4 border-main">
        <ul class="list-unstyled mb-0">
            @forelse($attendance->logs as $log)
                <li class="py-3 border-bottom border-subtle">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge-premium bg-primary-subtle text-primary fw-semibold">{{ $log->action }}</span>
                        <span class="text-low small">{{ $log->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                    <div class="text-high fw-medium">{{ $log->description }}</div>
                    <div class="text-low small mt-1">
                        Actor: <strong class="text-high">{{ $log->user?->name ?? 'System' }}</strong>
                        @if($log->ip_address)
                            &bull; IP: {{ $log->ip_address }}
                        @endif
                    </div>
                </li>
            @empty
                <li class="py-4 text-center text-low">
                    No audit logs available for this attendance record.
                </li>
            @endforelse
        </ul>
    </div>
</x-admin>
