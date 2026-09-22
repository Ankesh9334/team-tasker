<x-admin>
    <x-slot:title>
        Attendance & Leave Calendar | Team Tasker
    </x-slot:title>

    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <!-- FullCalendar JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>

    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Attendance & Leave Calendar</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">
                Visual overview of daily punches, shifts, leaves, and regularization requests.
                @if(isset($selectedUser))
                    Showing: <strong class="text-high">{{ $selectedUser->name }}</strong>
                @elseif($userId === 'all')
                    Showing: <strong class="text-high">All Employees</strong>
                @endif
            </p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if(Auth::user()->hasPermission('attendance.calendar_all') || Auth::user()->hasRole('super-admin'))
                <form action="{{ route('admin.attendance.calendar') }}" method="GET" class="d-flex align-items-center gap-2" style="min-width: 240px;">
                    <x-select name="user_id" :selected="$userId" placeholder="Select Employee..." onchange="this.form.submit()" class="w-100">
                        <option value="all" {{ $userId === 'all' ? 'selected' : '' }}>All Employees</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ (string)$userId === (string)$u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </x-select>
                </form>
            @endif

            <a href="{{ route('admin.attendance.requests') }}" class="btn-premium btn-premium-secondary d-flex align-items-center gap-1 shadow-sm">
                <i class="fas fa-list-alt me-1"></i> Requests List
            </a>
            <a href="{{ route('admin.attendance.myDaily') }}" class="btn-premium btn-premium-primary d-flex align-items-center gap-1 shadow-sm">
                <i class="fas fa-clock me-1"></i> My Attendance
            </a>
        </div>
    </div>

    <!-- Calendar Legend Bar -->
    <div class="glass-card p-3 mb-4 border-main">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="text-low small fw-semibold text-uppercase" style="letter-spacing: 0.5px;">Calendar Legend:</span>
            <div class="d-flex align-items-center flex-wrap gap-3 small">
                <span class="d-flex align-items-center gap-1">
                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #10b981;"></span>
                    <span class="text-high">Present</span>
                </span>
                <span class="d-flex align-items-center gap-1">
                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #0ea5e9;"></span>
                    <span class="text-high">Half Day</span>
                </span>
                <span class="d-flex align-items-center gap-1">
                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #ef4444;"></span>
                    <span class="text-high">Absent / Rejected</span>
                </span>
                <span class="d-flex align-items-center gap-1">
                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #8b5cf6;"></span>
                    <span class="text-high fw-semibold">Leave (Approved)</span>
                </span>
                <span class="d-flex align-items-center gap-1">
                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #f59e0b;"></span>
                    <span class="text-high">Pending Request</span>
                </span>
                <span class="d-flex align-items-center gap-1">
                    <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #06b6d4;"></span>
                    <span class="text-high">Overtime / Reg</span>
                </span>
            </div>
        </div>
    </div>

    <div class="glass-card p-4 border-main mb-4">
        <div id='calendar' style="min-height: 650px;"></div>
    </div>

    <style>
        .fc-theme-standard td, .fc-theme-standard th {
            border-color: var(--border-subtle);
        }
        .fc-theme-standard .fc-scrollgrid {
            border-color: var(--border-main);
        }
        .fc .fc-daygrid-day.fc-day-today {
            background-color: rgba(var(--primary-rgb), 0.08);
        }
        .fc .fc-toolbar-title {
            color: var(--text-high);
            font-weight: 600;
            font-size: 1.35rem;
        }
        .fc .fc-button-primary {
            background-color: var(--bg-input);
            border-color: var(--border-main);
            color: var(--text-high);
            text-transform: capitalize;
            font-weight: 500;
            padding: 6px 14px;
            border-radius: 6px;
        }
        .fc .fc-button-primary:not(:disabled).fc-button-active, 
        .fc .fc-button-primary:not(:disabled):active {
            background-color: var(--primary);
            border-color: var(--primary);
            color: #ffffff;
        }
        .fc .fc-button-primary:hover {
            background-color: var(--border-subtle);
        }
        .fc-event {
            cursor: pointer;
            border-radius: 5px;
            padding: 2px 6px;
            font-size: 0.82em;
            font-weight: 500;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        .fc-event:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }
        .fc .fc-col-header-cell-cushion {
            color: var(--text-low);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 8px 4px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var events = @json($events);
            
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,dayGridWeek,listWeek'
                },
                events: events,
                height: 'auto',
                eventClick: function(info) {
                    var props = info.event.extendedProps || {};
                    
                    if (props.is_request) {
                        document.getElementById('modalTypeHeader').innerText = (props.type || 'Request') + ' Details';
                        document.getElementById('calendarEventTitle').innerText = info.event.title;
                        
                        var employeeContainer = document.getElementById('calendarEventEmployeeContainer');
                        if (props.user_name) {
                            document.getElementById('calendarEventEmployee').innerText = props.user_name;
                            employeeContainer.classList.remove('d-none');
                        } else {
                            employeeContainer.classList.add('d-none');
                        }

                        document.getElementById('calendarEventDates').innerText = props.start_date + (props.end_date && props.end_date !== props.start_date ? ' to ' + props.end_date : '');
                        
                        var badge = document.getElementById('calendarEventStatusBadge');
                        badge.innerText = props.status;
                        badge.className = 'badge-premium px-2 py-1 ' + (props.status === 'Approved' ? 'bg-success text-white' : (props.status === 'Rejected' ? 'bg-danger text-white' : 'bg-warning text-dark'));
                        
                        document.getElementById('calendarEventReason').innerText = props.reason || 'No reason provided.';
                        
                        const notesContainer = document.getElementById('calendarEventActionNotesContainer');
                        if (props.action_notes) {
                            document.getElementById('calendarEventActionNotes').innerText = props.action_notes;
                            notesContainer.classList.remove('d-none');
                        } else {
                            notesContainer.classList.add('d-none');
                        }

                        const actionByContainer = document.getElementById('calendarEventActionByContainer');
                        if (props.action_by) {
                            document.getElementById('calendarEventActionBy').innerText = props.action_by;
                            actionByContainer.classList.remove('d-none');
                        } else {
                            actionByContainer.classList.add('d-none');
                        }

                        document.getElementById('requestDetailsSection').classList.remove('d-none');
                        document.getElementById('attendanceDetailsSection').classList.add('d-none');
                        
                        new bootstrap.Modal(document.getElementById('calendarEventModal')).show();
                    } else {
                        // Attendance record clicked
                        document.getElementById('modalTypeHeader').innerText = 'Attendance Details';
                        document.getElementById('calendarEventTitle').innerText = info.event.title;

                        var employeeContainer = document.getElementById('calendarEventEmployeeContainer');
                        if (props.user_name) {
                            document.getElementById('calendarEventEmployee').innerText = props.user_name;
                            employeeContainer.classList.remove('d-none');
                        } else {
                            employeeContainer.classList.add('d-none');
                        }

                        document.getElementById('attCheckIn').innerText = props.check_in || '--:--';
                        document.getElementById('attCheckOut').innerText = props.check_out || '--:--';
                        document.getElementById('attWorkingHours').innerText = props.working_hours || '0h 0m';
                        document.getElementById('attStatusBadge').innerText = props.status + (props.approval_status ? ' (' + props.approval_status + ')' : '');
                        document.getElementById('attRemarks').innerText = props.remarks || 'No remarks recorded.';

                        document.getElementById('requestDetailsSection').classList.add('d-none');
                        document.getElementById('attendanceDetailsSection').classList.remove('d-none');

                        new bootstrap.Modal(document.getElementById('calendarEventModal')).show();
                    }
                }
            });
            calendar.render();
        });
    </script>

    <!-- Calendar Event Modal -->
    <div class="modal fade" id="calendarEventModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-main shadow-lg">
                <div class="modal-header border-subtle">
                    <h5 class="modal-title fw-bold text-high" id="modalTypeHeader">Leave Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="calendarEventEmployeeContainer" class="d-none mb-3 pb-2 border-bottom border-subtle">
                        <small class="text-low d-block">Employee</small>
                        <h6 class="fw-bold text-high mb-0" id="calendarEventEmployee"></h6>
                    </div>

                    <h5 class="text-high fw-semibold mb-3" id="calendarEventTitle"></h5>

                    <!-- Request Section -->
                    <div id="requestDetailsSection">
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <strong class="text-low d-block small mb-1">Status</strong>
                                <span id="calendarEventStatusBadge" class="badge-premium px-2 py-1"></span>
                            </div>
                            <div class="col-6">
                                <strong class="text-low d-block small mb-1">Dates</strong>
                                <span id="calendarEventDates" class="text-high fw-semibold small"></span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <strong class="text-high d-block mb-1 small">Reason:</strong>
                            <div class="text-medium p-3 bg-dark bg-opacity-10 rounded border border-secondary border-opacity-25" id="calendarEventReason" style="white-space: pre-wrap;"></div>
                        </div>

                        <div id="calendarEventActionNotesContainer" class="d-none mb-3">
                            <strong class="text-high d-block mb-1 small">Reviewer Notes:</strong>
                            <div class="text-medium p-3 bg-dark bg-opacity-10 rounded border border-secondary border-opacity-25" id="calendarEventActionNotes" style="white-space: pre-wrap; color: var(--bs-warning);"></div>
                        </div>

                        <div id="calendarEventActionByContainer" class="d-none small text-low">
                            Reviewed by: <span id="calendarEventActionBy" class="text-high fw-semibold"></span>
                        </div>
                    </div>

                    <!-- Attendance Section -->
                    <div id="attendanceDetailsSection" class="d-none">
                        <div class="row g-2 text-center mb-3">
                            <div class="col-4 p-2 rounded bg-subtle border-subtle">
                                <small class="text-low d-block">Check In</small>
                                <span class="fw-bold text-high" id="attCheckIn"></span>
                            </div>
                            <div class="col-4 p-2 rounded bg-subtle border-subtle">
                                <small class="text-low d-block">Check Out</small>
                                <span class="fw-bold text-high" id="attCheckOut"></span>
                            </div>
                            <div class="col-4 p-2 rounded bg-subtle border-subtle">
                                <small class="text-low d-block">Hours</small>
                                <span class="fw-bold text-primary" id="attWorkingHours"></span>
                            </div>
                        </div>
                        <div class="mb-2">
                            <small class="text-low d-block">Status</small>
                            <span id="attStatusBadge" class="badge-premium bg-primary-subtle text-primary px-2 py-1"></span>
                        </div>
                        <div>
                            <small class="text-low d-block">Remarks</small>
                            <div class="text-medium p-2 bg-dark bg-opacity-10 rounded border border-secondary border-opacity-25 small" id="attRemarks"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-subtle">
                    <button type="button" class="btn-premium btn-premium-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</x-admin>
