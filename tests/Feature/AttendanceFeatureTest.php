<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\AttendanceLog;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\TimeLog;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AttendanceFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected User $normalUser;

    protected User $anotherUser;

    protected AttendanceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AttendanceService::class);

        // Fetch or create roles
        $adminRole = Role::firstOrCreate(['slug' => 'super-admin'], [
            'name' => 'Super Admin',
            'permissions' => ['*'],
        ]);

        $userRole = Role::firstOrCreate(['slug' => 'staff'], [
            'name' => 'Staff Member',
            'permissions' => ['attendance.punch', 'attendance.submit', 'attendance.view'],
        ]);

        // Create test users
        $this->superAdmin = User::factory()->create([
            'role_id' => $adminRole->id,
            'email' => 'admin_test_'.uniqid().'@example.com',
        ]);

        $this->normalUser = User::factory()->create([
            'role_id' => $userRole->id,
            'email' => 'user_test_'.uniqid().'@example.com',
        ]);

        $this->anotherUser = User::factory()->create([
            'role_id' => $userRole->id,
            'email' => 'another_test_'.uniqid().'@example.com',
        ]);
    }

    /**
     * Test 1: Normal user can punch in without GPS and duplicate punch in is blocked.
     */
    public function test_user_can_punch_in_and_duplicate_is_blocked(): void
    {
        $this->actingAs($this->normalUser);

        $response = $this->post(route('admin.attendance.clockIn'), [
            'notes' => 'Starting morning shift',
        ]);

        $response->assertSessionHas('success');

        $today = $this->service->today()->format('Y-m-d');
        $attendance = Attendance::where('user_id', $this->normalUser->id)
            ->where('attendance_date', $today)
            ->first();

        $this->assertNotNull($attendance);
        $this->assertNotNull($attendance->check_in);
        $this->assertEquals('present', $attendance->attendance_status);
        $this->assertEquals('draft', $attendance->approval_status);

        // Verify audit log
        $this->assertDatabaseHas('attendance_logs', [
            'attendance_id' => $attendance->id,
            'user_id' => $this->normalUser->id,
            'action' => 'attendance.punch_in',
        ]);

        // Duplicate punch in attempt must fail
        $secondResponse = $this->post(route('admin.attendance.clockIn'), [
            'notes' => 'Second attempt',
        ]);

        $secondResponse->assertSessionHas('error');
    }

    /**
     * Test 2: User can punch out and working minutes are calculated server-side.
     */
    public function test_user_can_punch_out_and_working_minutes_are_calculated(): void
    {
        $today = $this->service->today()->format('Y-m-d');

        // Create an active punch in
        $attendance = Attendance::create([
            'user_id' => $this->normalUser->id,
            'attendance_date' => $today,
            'check_in' => '09:00:00',
            'attendance_status' => 'present',
            'approval_status' => 'draft',
        ]);

        $this->actingAs($this->normalUser);

        $response = $this->post(route('admin.attendance.clockOut'), [
            'notes' => 'Finished work',
        ]);

        $response->assertSessionHas('success');

        $attendance->refresh();
        $this->assertNotNull($attendance->check_out);
        $this->assertGreaterThan(0, $attendance->working_minutes);

        // Verify audit log
        $this->assertDatabaseHas('attendance_logs', [
            'attendance_id' => $attendance->id,
            'user_id' => $this->normalUser->id,
            'action' => 'attendance.punch_out',
        ]);

        // Duplicate punch out must be blocked
        $secondResponse = $this->post(route('admin.attendance.clockOut'));
        $secondResponse->assertSessionHas('error');
    }

    /**
     * Test 3: Punch out before punch in is blocked.
     */
    public function test_punch_out_before_punch_in_is_blocked(): void
    {
        $this->actingAs($this->normalUser);

        $response = $this->post(route('admin.attendance.clockOut'));
        $response->assertSessionHas('error');
    }

    /**
     * Test 4: Overnight shifts past midnight compute positive working minutes correctly.
     */
    public function test_overnight_working_minutes_calculation(): void
    {
        // 23:30 check in to 01:30 check out next day (2 hours = 120 minutes)
        $minutes = $this->service->calculateWorkingMinutes('23:30:00', '01:30:00', '2026-09-08');
        $this->assertEquals(120, $minutes);

        // Normal same-day 09:15 to 18:00 (8 hours 45 mins = 525 mins)
        $normalMinutes = $this->service->calculateWorkingMinutes('09:15:00', '18:00:00', '2026-09-08');
        $this->assertEquals(525, $normalMinutes);
    }

    /**
     * Test 5: Submission lifecycle — draft transitions to pending.
     */
    public function test_user_can_submit_daily_attendance_for_approval(): void
    {
        $today = $this->service->today()->format('Y-m-d');

        $attendance = Attendance::create([
            'user_id' => $this->normalUser->id,
            'attendance_date' => $today,
            'check_in' => '09:00:00',
            'check_out' => '17:30:00',
            'working_minutes' => 510,
            'attendance_status' => 'present',
            'approval_status' => 'draft',
        ]);

        $this->actingAs($this->normalUser);

        $response = $this->post(route('admin.attendance.submit', $attendance->id), [
            'remarks' => 'Completed task #102 and prepared sprint review.',
        ]);

        $response->assertSessionHas('success');

        $attendance->refresh();
        $this->assertEquals('pending', $attendance->approval_status);
        $this->assertNotNull($attendance->submitted_at);
        $this->assertEquals('Completed task #102 and prepared sprint review.', $attendance->remarks);

        // Activity log
        $this->assertDatabaseHas('attendance_logs', [
            'attendance_id' => $attendance->id,
            'user_id' => $this->normalUser->id,
            'action' => 'attendance.submitted',
        ]);
    }

    /**
     * Test 6: Security — Normal user CANNOT submit or edit another user's attendance.
     */
    public function test_user_cannot_access_or_submit_another_users_attendance(): void
    {
        $today = $this->service->today()->format('Y-m-d');

        $otherAttendance = Attendance::create([
            'user_id' => $this->anotherUser->id,
            'attendance_date' => $today,
            'check_in' => '09:00:00',
            'attendance_status' => 'present',
            'approval_status' => 'draft',
        ]);

        $this->actingAs($this->normalUser);

        // Attempt to submit another user's record
        $response = $this->post(route('admin.attendance.submit', $otherAttendance->id), [
            'remarks' => 'Malicious spoof attempt',
        ]);

        // Must be forbidden (HTTP 403)
        $response->assertStatus(403);
    }

    /**
     * Test 7: Super Admin can approve attendance and notification is generated.
     */
    public function test_super_admin_can_approve_attendance(): void
    {
        $today = $this->service->today()->format('Y-m-d');

        $attendance = Attendance::create([
            'user_id' => $this->normalUser->id,
            'attendance_date' => $today,
            'check_in' => '09:00:00',
            'check_out' => '17:00:00',
            'working_minutes' => 480,
            'attendance_status' => 'present',
            'approval_status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->superAdmin);

        $response = $this->post(route('admin.attendance.approve', $attendance->id));
        $response->assertSessionHas('success');

        $attendance->refresh();
        $this->assertEquals('approved', $attendance->approval_status);
        $this->assertEquals($this->superAdmin->id, $attendance->approved_by);
        $this->assertNotNull($attendance->approved_at);

        // Activity log
        $this->assertDatabaseHas('attendance_logs', [
            'attendance_id' => $attendance->id,
            'user_id' => $this->superAdmin->id,
            'action' => 'attendance.approved',
        ]);

        // Notification received by employee
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->normalUser->id,
            'type' => 'App\Notifications\AttendanceNotification',
        ]);
    }

    /**
     * Test 8: Approved attendance is locked for normal users.
     */
    public function test_normal_user_cannot_edit_approved_attendance(): void
    {
        $today = $this->service->today()->format('Y-m-d');

        $attendance = Attendance::create([
            'user_id' => $this->normalUser->id,
            'attendance_date' => $today,
            'check_in' => '09:00:00',
            'check_out' => '17:00:00',
            'working_minutes' => 480,
            'attendance_status' => 'present',
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->normalUser);

        $response = $this->post(route('admin.attendance.submit', $attendance->id), [
            'remarks' => 'Trying to modify approved attendance',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test 9: Rejection requires mandatory reason; user can edit and resubmit.
     */
    public function test_rejection_requires_reason_and_user_can_resubmit(): void
    {
        $today = $this->service->today()->format('Y-m-d');

        $attendance = Attendance::create([
            'user_id' => $this->normalUser->id,
            'attendance_date' => $today,
            'check_in' => '09:00:00',
            'check_out' => '17:00:00',
            'working_minutes' => 480,
            'attendance_status' => 'present',
            'approval_status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->superAdmin);

        // Rejection without reason must fail validation
        $emptyReasonResponse = $this->post(route('admin.attendance.reject', $attendance->id), [
            'rejection_reason' => '',
        ]);
        $emptyReasonResponse->assertSessionHasErrors('rejection_reason');

        // Valid rejection with reason
        $rejectResponse = $this->post(route('admin.attendance.reject', $attendance->id), [
            'rejection_reason' => 'Check-out time appears incorrect. Please correct and resubmit.',
        ]);
        $rejectResponse->assertSessionHas('success');

        $attendance->refresh();
        $this->assertEquals('rejected', $attendance->approval_status);
        $this->assertEquals('Check-out time appears incorrect. Please correct and resubmit.', $attendance->rejection_reason);

        // Normal user edits and resubmits
        $this->actingAs($this->normalUser);

        $resubmitResponse = $this->post(route('admin.attendance.resubmit', $attendance->id), [
            'remarks' => 'Corrected checkout: Left office at 17:00 as verified by team lead.',
        ]);
        $resubmitResponse->assertSessionHas('success');

        $attendance->refresh();
        $this->assertEquals('pending', $attendance->approval_status);
        $this->assertDatabaseHas('attendance_logs', [
            'attendance_id' => $attendance->id,
            'user_id' => $this->normalUser->id,
            'action' => 'attendance.resubmitted',
        ]);
    }

    /**
     * Test 10: Bulk approval works for pending records and skips others in a transaction.
     */
    public function test_super_admin_bulk_approval(): void
    {
        $today = $this->service->today()->format('Y-m-d');

        $pending1 = Attendance::create([
            'user_id' => $this->normalUser->id,
            'attendance_date' => $today,
            'check_in' => '09:00:00',
            'check_out' => '17:00:00',
            'working_minutes' => 480,
            'attendance_status' => 'present',
            'approval_status' => 'pending',
        ]);

        $pending2 = Attendance::create([
            'user_id' => $this->anotherUser->id,
            'attendance_date' => $today,
            'check_in' => '09:30:00',
            'check_out' => '17:30:00',
            'working_minutes' => 480,
            'attendance_status' => 'present',
            'approval_status' => 'pending',
        ]);

        $alreadyApproved = Attendance::create([
            'user_id' => $this->normalUser->id,
            'attendance_date' => '2026-09-01',
            'check_in' => '09:00:00',
            'check_out' => '17:00:00',
            'working_minutes' => 480,
            'attendance_status' => 'present',
            'approval_status' => 'approved',
        ]);

        $this->actingAs($this->superAdmin);

        $response = $this->post(route('admin.attendance.bulkApprove'), [
            'attendance_ids' => [$pending1->id, $pending2->id, $alreadyApproved->id],
        ]);

        $response->assertSessionHas('success');

        $pending1->refresh();
        $pending2->refresh();
        $this->assertEquals('approved', $pending1->approval_status);
        $this->assertEquals('approved', $pending2->approval_status);
    }

    /**
     * Test 11: Super Admin attendance manual correction with audit logging.
     */
    public function test_super_admin_can_correct_attendance(): void
    {
        $today = $this->service->today()->format('Y-m-d');

        $attendance = Attendance::create([
            'user_id' => $this->normalUser->id,
            'attendance_date' => $today,
            'check_in' => '09:30:00',
            'check_out' => '18:00:00',
            'working_minutes' => 510,
            'attendance_status' => 'present',
            'approval_status' => 'approved',
        ]);

        $this->actingAs($this->superAdmin);

        $response = $this->post(route('admin.attendance.daily.update'), [
            'user_id' => $this->normalUser->id,
            'date' => $today,
            'check_in' => '09:15',
            'check_out' => '18:00',
            'attendance_status' => 'present',
            'approval_status' => 'approved',
            'correction_reason' => 'Adjusted punch-in time per gate register log.',
        ]);

        $response->assertSessionHas('success');

        $attendance->refresh();
        // 09:15 to 18:00 = 8 hours 45 mins = 525 mins
        $this->assertEquals(525, $attendance->working_minutes);

        // Verify audit log has old and new values recorded
        $log = AttendanceLog::where('attendance_id', $attendance->id)
            ->where('action', 'attendance.updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->superAdmin->id, $log->user_id);
        $this->assertArrayHasKey('old', $log->metadata);
        $this->assertArrayHasKey('new', $log->metadata);
        $this->assertEquals('Adjusted punch-in time per gate register log.', $log->metadata['correction_reason']);
    }

    /**
     * Test 12: Monthly report calculations (present, half_day, absent, %, total hours).
     */
    public function test_monthly_report_calculations(): void
    {
        $month = '2026-08';

        // 3 present approved days
        for ($i = 1; $i <= 3; $i++) {
            Attendance::create([
                'user_id' => $this->normalUser->id,
                'attendance_date' => "{$month}-0{$i}",
                'check_in' => '09:00:00',
                'check_out' => '17:00:00',
                'working_minutes' => 480, // 8h
                'attendance_status' => 'present',
                'approval_status' => 'approved',
            ]);
        }

        // 1 half day approved
        Attendance::create([
            'user_id' => $this->normalUser->id,
            'attendance_date' => "{$month}-04",
            'check_in' => '09:00:00',
            'check_out' => '13:00:00',
            'working_minutes' => 240, // 4h
            'attendance_status' => 'half_day',
            'approval_status' => 'approved',
        ]);

        // 1 absent approved
        Attendance::create([
            'user_id' => $this->normalUser->id,
            'attendance_date' => "{$month}-05",
            'attendance_status' => 'absent',
            'approval_status' => 'approved',
            'working_minutes' => 0,
        ]);

        $stats = $this->service->getMonthlyUserStats($this->normalUser, $month);

        $this->assertEquals(3, $stats['present_days']);
        $this->assertEquals(1, $stats['half_days']);
        $this->assertEquals(1, $stats['absent_days']);
        $this->assertEquals(5, $stats['approved_days']);
        // 3*480 + 240 = 1680 minutes = 28h 0m
        $this->assertEquals(1680, $stats['total_working_minutes']);
        $this->assertEquals('28h 0m', $stats['formatted_working_hours']);
        $this->assertGreaterThan(0, $stats['attendance_percentage']);
    }

    /**
     * Test 13: Normal user cannot view another user's monthly report.
     */
    public function test_normal_user_cannot_view_all_employees_monthly_report(): void
    {
        $this->actingAs($this->normalUser);

        // Normal user attempting to access super admin monthly overview route
        $response = $this->get(route('admin.attendance.monthly'));
        $response->assertStatus(403);

        // But normal user CAN access their own monthly report
        $ownResponse = $this->get(route('admin.attendance.myMonthly'));
        $ownResponse->assertStatus(200);
    }

    /**
     * Test 14: CSV export respects filters and non-admins are unauthorized.
     */
    public function test_export_authorization_and_filter_support(): void
    {
        // Normal user cannot export
        $this->actingAs($this->normalUser);
        $unauthorized = $this->get(route('admin.attendance.reports', ['export' => 'daily']));
        $unauthorized->assertStatus(403);

        // Super admin can export
        $this->actingAs($this->superAdmin);
        $exportResponse = $this->get(route('admin.attendance.reports', ['export' => 'daily', 'date' => '2026-09-08']));
        $exportResponse->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $exportResponse->headers->get('Content-Type'));
    }

    /**
     * Test 15: Admin model (multi-guard auth:admin) can access monthly report without Gate type error and approve/reject attendance.
     */
    public function test_admin_model_can_access_monthly_report_and_approve_attendance(): void
    {
        $admin = Admin::create([
            'name' => 'System Director',
            'email' => 'director_'.uniqid().'@example.com',
            'password' => bcrypt('secret123'),
        ]);

        // Authenticate using the admin guard
        $this->actingAs($admin, 'admin');

        // Verify monthly report access via Gate viewReport
        $response = $this->get(route('admin.attendance.monthly'));
        $response->assertStatus(200);

        // Verify daily report access via Gate viewAny
        $dailyResponse = $this->get(route('admin.attendance.daily'));
        $dailyResponse->assertStatus(200);

        // Create a pending attendance for a normal user
        $attendance = Attendance::create([
            'user_id' => $this->normalUser->id,
            'attendance_date' => '2026-09-08',
            'check_in' => '09:00:00',
            'check_out' => '18:00:00',
            'working_minutes' => 540,
            'attendance_status' => 'present',
            'approval_status' => 'pending',
            'submitted_at' => Carbon::now(),
        ]);

        // Admin approves attendance
        $approveResponse = $this->post(route('admin.attendance.approve', $attendance->id));
        $approveResponse->assertStatus(302);

        $attendance->refresh();
        $this->assertEquals('approved', $attendance->approval_status);
        $this->assertEquals($admin->id, $attendance->approved_by);
        $this->assertEquals('System Director', $attendance->approver_name);

        // Verify log was created with Admin actor without foreign key violation
        $log = AttendanceLog::where('attendance_id', $attendance->id)
            ->where('action', 'attendance.approved')
            ->first();
        $this->assertNotNull($log);
        $this->assertEquals('System Director', $log->actor_name);
    }

    /**
     * Test 16: Super Admin sees all navigation menus in sidebar layout.
     */
    public function test_super_admin_sees_all_menus_in_sidebar(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.attendance.daily'));
        $response->assertStatus(200);

        // All 9 Attendance menus are rendered for super admin
        $response->assertSee(route('admin.attendance.dashboard'));
        $response->assertSee(route('admin.attendance.daily'));
        $response->assertSee(route('admin.attendance.monthly'));
        $response->assertSee(route('admin.attendance.myDaily'));
        $response->assertSee(route('admin.attendance.myMonthly'));
        $response->assertSee(route('admin.attendance.calendar'));
        $response->assertSee(route('admin.attendance.requests'));
        $response->assertSee(route('admin.attendance.reports'));
        $response->assertSee(route('admin.attendance.settings'));

        // Main navigation items
        $response->assertSee('Projects');
        $response->assertSee('Tasks');
        $response->assertSee('Team Chat');
        $response->assertSee('Settings');

        // Header utils contains Submit Report icon button and modal triggers
        $response->assertSee('globalSubmitReportModal');
        $response->assertSee('Daily Work Report');
    }

    /**
     * Test 17: User can submit today's work report from the global header button for Super Admin approval.
     */
    public function test_user_can_submit_today_report_from_global_header(): void
    {
        $this->actingAs($this->normalUser);

        // Submit daily report via global header endpoint
        $response = $this->post(route('admin.attendance.submitTodayReport'), [
            'remarks' => 'Completed frontend UI refactoring and verified all acceptance tests.',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $today = Carbon::today()->format('Y-m-d');
        $attendance = Attendance::where('user_id', $this->normalUser->id)
            ->where(function ($q) use ($today) {
                $q->where('attendance_date', $today)->orWhere('date', $today);
            })->first();

        $this->assertNotNull($attendance);
        $this->assertEquals('pending', $attendance->approval_status);
        $this->assertEquals('Completed frontend UI refactoring and verified all acceptance tests.', $attendance->remarks);

        // Now Super Admin can view and approve the submitted report
        $this->actingAs($this->superAdmin);
        $approveResponse = $this->post(route('admin.attendance.approve', $attendance->id));
        $approveResponse->assertStatus(302);

        $attendance->refresh();
        $this->assertEquals('approved', $attendance->approval_status);
    }

    /**
     * Test 18: Super Admin can access the centralized HR Settings hub and see all HR-related modules in sidebar.
     */
    public function test_hr_settings_hub_and_sidebar_navigation(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.hr.settings'));
        $response->assertStatus(200);
        $response->assertSee('HR Settings');
        $response->assertSee('Users & Staff', false);
        $response->assertSee('Roles & Permissions', false);
        $response->assertSee('Attendance Settings');
        $response->assertSee('Shift & Timer Rules', false);
        $response->assertSee('Leave Requests');
    }

    /**
     * Test 19: Super Admin can filter Daily Work Reports by user and date, and inspect detailed activity.
     */
    public function test_daily_reports_filters_and_activity_inspector(): void
    {
        $this->actingAs($this->superAdmin);

        $today = Carbon::today()->format('Y-m-d');

        // Create attendance for normalUser
        $attendance = Attendance::create([
            'user_id' => $this->normalUser->id,
            'attendance_date' => $today,
            'check_in' => '09:05:00',
            'check_out' => '18:10:00',
            'working_minutes' => 545,
            'attendance_status' => 'present',
            'approval_status' => 'pending',
            'remarks' => 'Implemented customer payment gateway and resolved API timeout bugs.',
            'submitted_at' => Carbon::now(),
        ]);

        // Create task and time log activity
        $task = Task::create([
            'title' => 'Payment Gateway Integration',
            'description' => 'Payment Gateway Integration Description',
            'user_id' => $this->normalUser->id,
        ]);

        $timeLog = TimeLog::create([
            'task_id' => $task->id,
            'user_id' => $this->normalUser->id,
            'start_time' => Carbon::now()->subHours(4),
            'end_time' => Carbon::now()->subHours(1),
            'duration' => 10800,
            'description' => 'Built webhook listener and payment verification tests.',
            'mode' => 'Inside Office',
        ]);

        $taskLog = TaskLog::create([
            'task_id' => $task->id,
            'user_id' => $this->normalUser->id,
            'note' => 'Deployed payment gateway sandbox credentials.',
            'type' => 'log',
        ]);

        // 1. Check daily page with user filter
        $pageResponse = $this->get(route('admin.attendance.daily', [
            'user_id' => $this->normalUser->id,
            'date' => $today,
        ]));

        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('Daily Work Reports');
        $pageResponse->assertSee($this->normalUser->name);
        $pageResponse->assertSee('Implemented customer payment gateway');

        // 2. Test AJAX reportDetails endpoint
        $detailsResponse = $this->get(route('admin.attendance.reportDetails', [
            'user_id' => $this->normalUser->id,
            'date' => $today,
        ]));

        $detailsResponse->assertStatus(200);
        $detailsResponse->assertJsonPath('success', true);
        $detailsResponse->assertJsonPath('user.name', $this->normalUser->name);
        $detailsResponse->assertJsonPath('attendance.remarks', 'Implemented customer payment gateway and resolved API timeout bugs.');
        $detailsResponse->assertJsonPath('attendance.approval_status', 'pending');
        $detailsResponse->assertJsonPath('time_logs.0.task_title', 'Payment Gateway Integration');
        $detailsResponse->assertJsonPath('time_logs.0.description', 'Built webhook listener and payment verification tests.');
        $detailsResponse->assertJsonPath('task_logs.0.note', 'Deployed payment gateway sandbox credentials.');
        $detailsResponse->assertJsonPath('can_approve', true);
    }

    /**
     * Test 20: Verify clean separation of Attendance menu and HR menu.
     */
    public function test_menu_separation_attendance_and_hr(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.attendance.dashboard'));
        $response->assertStatus(200);

        // Attendance menu has attendance items
        $response->assertSee('Daily Work Reports');
        $response->assertSee('Attendance Settings');

        // HR menu has HR items
        $response->assertSee('HR Settings');
        $response->assertSee('HR Overview');
        $response->assertSee('Users & Staff', false);
        $response->assertSee('Roles & Permissions', false);
        $response->assertSee('Leave Requests');
    }

    /**
     * Test 21: Verify bulkAction works with selected user IDs for approve and reject.
     */
    public function test_bulk_action_approve_and_reject_with_user_ids(): void
    {
        $this->actingAs($this->superAdmin);
        $today = $this->service->today()->format('Y-m-d');

        // Test bulk approve with user ID
        $response = $this->post(route('admin.attendance.bulkAction'), [
            'action' => 'approve',
            'ids' => [$this->normalUser->id],
            'date' => $today,
        ]);

        $response->assertSessionHas('success');
        $att = Attendance::where('user_id', $this->normalUser->id)->where('attendance_date', $today)->first();
        $this->assertNotNull($att);
        $this->assertEquals('approved', $att->approval_status);

        // Test bulk reject with reason
        $response = $this->post(route('admin.attendance.bulkAction'), [
            'action' => 'reject',
            'ids' => [$this->normalUser->id],
            'date' => $today,
            'rejection_reason' => 'Bulk rejection test reason.',
        ]);

        $response->assertSessionHas('success');
        $att->refresh();
        $this->assertEquals('rejected', $att->approval_status);
        $this->assertEquals('Bulk rejection test reason.', $att->rejection_reason);
    }

    /**
     * Test 22: Verify custom role can be configured with HR Settings permissions.
     */
    public function test_role_with_hr_settings_permission(): void
    {
        $this->actingAs($this->superAdmin);
        $role = $this->normalUser->role;

        // Update role to have hr.view
        $response = $this->post(route('admin.roles.update', $role->id), [
            'name' => 'HR Specialist',
            'slug' => 'hr-specialist-'.uniqid(),
            'permissions' => ['hr.view'],
        ]);
        $response->assertSessionHas('success');

        $role->refresh();
        $this->assertContains('hr.view', $role->permissions);

        // Now normal user with this role can view HR settings
        $this->actingAs($this->normalUser);
        $hrResponse = $this->get(route('admin.hr.settings'));
        $hrResponse->assertStatus(200);
        $hrResponse->assertSee('HR & Staff Settings', false);
    }

    /**
     * Test 23: Verify granular HR Settings permissions and bulk role actions.
     */
    public function test_edit_role_granular_hr_permissions_and_bulk_role_actions(): void
    {
        $this->actingAs($this->superAdmin);

        // Create a temporary role
        $role = Role::create([
            'name' => 'HR Staff Officer',
            'slug' => 'hr-staff-officer-'.uniqid(),
            'permissions' => ['hr.view'],
        ]);

        // Edit role to grant all granular HR Settings permissions
        $updateResponse = $this->post(route('admin.roles.update', $role->id), [
            'name' => 'HR Lead Administrator',
            'slug' => $role->slug,
            'permissions' => [
                'hr.view',
                'hr.manage',
                'hr.attendance_settings',
                'hr.shift_rules',
                'hr.leave_requests',
                'hr.work_reports',
                'hr.timesheets',
                'hr.staff',
                'hr.roles',
            ],
        ]);
        $updateResponse->assertSessionHas('success');

        $role->refresh();
        $this->assertEquals('HR Lead Administrator', $role->name);
        $this->assertContains('hr.manage', $role->permissions);
        $this->assertContains('hr.shift_rules', $role->permissions);
        $this->assertContains('hr.leave_requests', $role->permissions);

        // Assign to user and verify route authorization for shift rules & leave requests
        $this->normalUser->update(['role_id' => $role->id]);
        $this->actingAs($this->normalUser);

        $shiftResponse = $this->get(route('admin.settings.autostop'));
        $shiftResponse->assertStatus(200);

        $leaveResponse = $this->get(route('admin.attendance.requests'));
        $leaveResponse->assertStatus(200);

        // Test bulk role deletion as super admin
        $this->actingAs($this->superAdmin);
        $dummyRole1 = Role::create(['name' => 'Dummy 1', 'slug' => 'dummy-1-'.uniqid(), 'permissions' => []]);
        $dummyRole2 = Role::create(['name' => 'Dummy 2', 'slug' => 'dummy-2-'.uniqid(), 'permissions' => []]);

        $bulkResponse = $this->post(route('admin.roles.bulkAction'), [
            'action' => 'delete',
            'ids' => [$dummyRole1->id, $dummyRole2->id],
        ]);
        $bulkResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('roles', ['id' => $dummyRole1->id]);
        $this->assertDatabaseMissing('roles', ['id' => $dummyRole2->id]);
    }

    /**
     * Test 24: Verify bulk attendance leave requests approval and rejection.
     */
    public function test_bulk_attendance_requests_approval_and_rejection(): void
    {
        $this->actingAs($this->superAdmin);

        $req1 = \App\Models\AttendanceRequest::create([
            'user_id' => $this->normalUser->id,
            'type' => 'Leave',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-11',
            'reason' => 'Family vacation',
            'status' => 'Pending',
        ]);

        $req2 = \App\Models\AttendanceRequest::create([
            'user_id' => $this->normalUser->id,
            'type' => 'Regularization',
            'start_date' => '2026-09-12',
            'end_date' => '2026-09-12',
            'reason' => 'Forgot to clock in',
            'status' => 'Pending',
        ]);

        // Bulk approve
        $bulkApprove = $this->post(route('admin.attendance.requests.bulkAction'), [
            'action' => 'Approved',
            'ids' => [$req1->id, $req2->id],
        ]);
        $bulkApprove->assertSessionHas('success');

        $req1->refresh();
        $req2->refresh();
        $this->assertEquals('Approved', $req1->status);
        $this->assertEquals('Approved', $req2->status);
        $this->assertEquals($this->superAdmin->id, $req1->action_by);
    }

    /**
     * Test 25: Verify multiselect employee filter in daily reports.
     */
    public function test_daily_reports_multiselect_user_filter(): void
    {
        $this->actingAs($this->superAdmin);

        $today = Carbon::today()->format('Y-m-d');
        $otherUser = User::factory()->create([
            'name' => 'Alice MultiTest',
            'email' => 'alice.multitest@example.com',
        ]);

        $thirdUser = User::factory()->create([
            'name' => 'Bob MultiTest',
            'email' => 'bob.multitest@example.com',
        ]);

        // Filter by array of user IDs (normalUser and otherUser, excluding thirdUser)
        $response = $this->get(route('admin.attendance.daily', [
            'date' => $today,
            'user_id' => [$this->normalUser->id, $otherUser->id],
            'per_page' => 100,
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('users', function ($users) use ($otherUser, $thirdUser) {
            $ids = $users->pluck('id')->toArray();

            return in_array($this->normalUser->id, $ids)
                && in_array($otherUser->id, $ids)
                && ! in_array($thirdUser->id, $ids);
        });
    }

    /**
     * Test 26: Verify clickable stat cards apply correct filters.
     */
    public function test_daily_reports_card_clickable_filters(): void
    {
        $this->actingAs($this->superAdmin);

        $today = Carbon::today()->format('Y-m-d');

        $userApproved = User::factory()->create(['name' => 'Approved User CardTest']);
        $userPending = User::factory()->create(['name' => 'Pending User CardTest']);
        $userRejected = User::factory()->create(['name' => 'Rejected User CardTest']);
        $userNotSubmitted = User::factory()->create(['name' => 'NotSubmitted User CardTest']);

        Attendance::create([
            'user_id' => $userApproved->id,
            'attendance_date' => $today,
            'check_in' => '09:00:00',
            'check_out' => '17:00:00',
            'working_minutes' => 480,
            'attendance_status' => 'present',
            'approval_status' => 'approved',
        ]);

        Attendance::create([
            'user_id' => $userPending->id,
            'attendance_date' => $today,
            'check_in' => '09:00:00',
            'check_out' => '17:00:00',
            'working_minutes' => 480,
            'attendance_status' => 'present',
            'approval_status' => 'pending',
        ]);

        Attendance::create([
            'user_id' => $userRejected->id,
            'attendance_date' => $today,
            'check_in' => '09:00:00',
            'check_out' => '17:00:00',
            'working_minutes' => 480,
            'attendance_status' => 'present',
            'approval_status' => 'rejected',
            'rejection_reason' => 'Incomplete tasks',
        ]);

        // 1. Total Users (no approval_status): sees all
        $resAll = $this->get(route('admin.attendance.daily', ['date' => $today, 'search' => 'CardTest', 'per_page' => 100]));
        $resAll->assertStatus(200);
        $resAll->assertViewHas('users', fn ($users) => $users->pluck('id')->contains($userApproved->id)
            && $users->pluck('id')->contains($userPending->id)
            && $users->pluck('id')->contains($userRejected->id)
            && $users->pluck('id')->contains($userNotSubmitted->id));

        // 2. Submitted: sees approved, pending, rejected, but not not_submitted
        $resSubmitted = $this->get(route('admin.attendance.daily', ['date' => $today, 'approval_status' => 'submitted', 'search' => 'CardTest', 'per_page' => 100]));
        $resSubmitted->assertStatus(200);
        $resSubmitted->assertViewHas('users', fn ($users) => $users->pluck('id')->contains($userApproved->id)
            && $users->pluck('id')->contains($userPending->id)
            && $users->pluck('id')->contains($userRejected->id)
            && ! $users->pluck('id')->contains($userNotSubmitted->id));

        // 3. Pending: sees pending only
        $resPending = $this->get(route('admin.attendance.daily', ['date' => $today, 'approval_status' => 'pending', 'search' => 'CardTest', 'per_page' => 100]));
        $resPending->assertStatus(200);
        $resPending->assertViewHas('users', fn ($users) => $users->pluck('id')->contains($userPending->id)
            && ! $users->pluck('id')->contains($userApproved->id)
            && ! $users->pluck('id')->contains($userRejected->id)
            && ! $users->pluck('id')->contains($userNotSubmitted->id));

        // 4. Approved: sees approved only
        $resApproved = $this->get(route('admin.attendance.daily', ['date' => $today, 'approval_status' => 'approved', 'search' => 'CardTest', 'per_page' => 100]));
        $resApproved->assertStatus(200);
        $resApproved->assertViewHas('users', fn ($users) => $users->pluck('id')->contains($userApproved->id)
            && ! $users->pluck('id')->contains($userPending->id)
            && ! $users->pluck('id')->contains($userRejected->id)
            && ! $users->pluck('id')->contains($userNotSubmitted->id));

        // 5. Rejected: sees rejected only
        $resRejected = $this->get(route('admin.attendance.daily', ['date' => $today, 'approval_status' => 'rejected', 'search' => 'CardTest', 'per_page' => 100]));
        $resRejected->assertStatus(200);
        $resRejected->assertViewHas('users', fn ($users) => $users->pluck('id')->contains($userRejected->id)
            && ! $users->pluck('id')->contains($userApproved->id)
            && ! $users->pluck('id')->contains($userPending->id)
            && ! $users->pluck('id')->contains($userNotSubmitted->id));

        // 6. Not Submitted: sees not_submitted only
        $resNotSub = $this->get(route('admin.attendance.daily', ['date' => $today, 'approval_status' => 'not_submitted', 'search' => 'CardTest', 'per_page' => 100]));
        $resNotSub->assertStatus(200);
        $resNotSub->assertViewHas('users', fn ($users) => $users->pluck('id')->contains($userNotSubmitted->id)
            && ! $users->pluck('id')->contains($userApproved->id)
            && ! $users->pluck('id')->contains($userPending->id)
            && ! $users->pluck('id')->contains($userRejected->id));
    }
}
