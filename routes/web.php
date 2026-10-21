<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskLogController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ChunkUploadController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AdminClientController;
use App\Http\Controllers\ClientImportController;
use App\Http\Controllers\ClientEmailTemplateController;
use App\Http\Controllers\ClientEmailController;
use App\Http\Controllers\UnmatchedEmailController;
use App\Http\Controllers\InboundEmailWebhookController;
use App\Http\Controllers\EnquiryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Client;
use App\Models\User;
use App\Models\Admin;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\HolidayController;



// Auth Controller
Route::controller(AuthController::class)->group(function () {
    Route::get('/login', 'loginPage')->name('login');
    Route::get('/', 'loginPage')->name('loginPage');
    Route::post('/login', 'login')->name('login_submit');
    Route::get('/register', 'registerPage')->name('registerPage');
    Route::post('/register', 'register')->name('register');

    // Client Registration
    Route::get('/client/register', 'clientRegisterPage')->name('clientRegisterPage');
    Route::post('/client/register', 'clientRegister')->name('clientRegister');

    // OTP Verification
    Route::get('/verify-otp', 'verifyOtpPage')->name('verifyOtpPage');
    Route::post('/verify-otp', 'verifyOtp')->name('verifyOtp');
    Route::post('/resend-otp', 'resendOtp')->name('resendOtp');

    Route::get('/forgotPassword', 'forgotPasswordPage')->name('forgotPasswordPage');
    Route::post('/forgotPassword', 'forgotPassword')->name('forgotPassword');
    Route::get('/reset-password', 'resetPasswordPage')->name('resetPasswordPage');
    Route::post('/reset-password', 'resetPassword')->name('resetPassword');
    Route::get('/logout', 'logout')->name('logout');
    Route::post('/profile/update', 'updateProfile')->name('profile.update');
});

// Search Controller
Route::middleware(['web', 'auth:web,admin'])->get('/search', [SearchController::class, 'index'])->name('search.global');

// Notifications
Route::middleware(['web', 'auth:web,admin,client'])->group(function () {
    Route::get('/notifications/{id}/read', [NotificationController::class, 'readAndRedirect'])->name('notifications.readAndRedirect');
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/update-fcm-token', function (Request $request) {
        $request->validate(['token' => 'required|string']);
        $user = auth('client')->user() ?? auth('web')->user() ?? auth('admin')->user();
        if ($user) {
            $tokens = json_decode($user->fcm_token, true);
            if (! is_array($tokens)) {
                $tokens = $user->fcm_token ? [$user->fcm_token] : [];
            }
            if (! in_array($request->token, $tokens)) {
                $tokens[] = $request->token;
                if (count($tokens) > 5) {
                    $tokens = array_slice($tokens, -5);
                }
                $user->fcm_token = json_encode($tokens);
                $user->save();
            }

            // Sync with counterpart
            if (! $user instanceof Client) {
                $syncTokens = json_encode($tokens);
                if ($user instanceof Admin) {
                    User::where('email', $user->email)->update(['fcm_token' => $syncTokens]);
                } elseif ($user instanceof User) {
                    Admin::where('email', $user->email)->update(['fcm_token' => $syncTokens]);
                }
            }
        }

        return response()->json(['success' => true]);
    })->name('update.fcm_token');
});

// Chunk Upload Route
Route::post('/upload-chunk', [ChunkUploadController::class, 'upload'])->name('upload.chunk')->middleware(['web']);

// Public Enquiry Route
Route::get('/enquire', [EnquiryController::class, 'create'])->name('enquiry.create');
Route::post('/enquire', [EnquiryController::class, 'store'])->name('enquiry.store');

// Project Controller
Route::middleware(['web', 'auth:web,admin'])->controller(ProjectController::class)->prefix('admin/projects')->group(function () {
    Route::get('/', 'index')->name('admin.projects.index')->middleware('permission:projects.view');
    Route::get('/create', 'create')->name('admin.projects.create')->middleware('permission:projects.create');
    Route::post('/', 'store')->name('admin.projects.store')->middleware('permission:projects.create');
    Route::get('/{id}', 'show')->name('admin.projects.show')->middleware('permission:projects.view');
    Route::post('/{id}/assign-task', 'assignTask')->name('admin.projects.assignTask')->middleware('permission:projects.edit');
    Route::get('/{id}/edit', 'edit')->name('admin.projects.edit')->middleware('permission:projects.edit');
    Route::post('/{id}/update', 'update')->name('admin.projects.update')->middleware('permission:projects.edit');
    Route::delete('/{id}', 'destroy')->name('admin.projects.destroy')->middleware('permission:projects.delete');
});

// Todo Controller
Route::middleware(['web', 'auth:web,admin', 'permission:tasks.todo'])->get('/admin/todos', [TodoController::class, 'index'])->name('admin.todos.index');

// Task Controller
Route::middleware(['web', 'auth:web,admin'])->controller(TaskController::class)->prefix('admin/tasks')->group(function () {
    Route::get('/dashboard', 'dashboard')->name('dashboard')->middleware('permission:dashboard.view');
    Route::get('/api/users/search', 'searchUsers')->name('admin.api.users.search');
    Route::get('/', 'index')->name('index')->middleware('permission:tasks.my_tasks');
    Route::get('activity', 'activity')->name('tasks.activity')->middleware('permission:tasks.activity');
    Route::get('board', 'board')->name('tasks.board')->middleware('permission:tasks.board');
    Route::get('calendar', 'calendar')->name('tasks.calendar')->middleware('permission:tasks.calendar');
    Route::get('events', 'calendarEvents')->name('tasks.calendar.events')->middleware('permission:tasks.calendar');
    Route::get('gantt', 'gantt')->name('tasks.gantt')->middleware('permission:tasks.gantt');
    Route::get('gantt-data', 'ganttData')->name('tasks.gantt.data')->middleware('permission:tasks.gantt');
    Route::get('create', 'create')->name('create')->middleware('permission:tasks.create');
    Route::post('store', 'store')->name('store')->middleware('permission:tasks.create');
    Route::get('details/{id}', 'show')->name('details')->middleware('permission:tasks.view');
    Route::get('edit/{id}', 'edit')->name('edit')->middleware('permission:tasks.edit');
    Route::post('update/{id}', 'update')->name('update')->middleware('permission:tasks.edit');
    Route::get('delete/{id}', 'destroy')->name('delete')->middleware('permission:tasks.delete');
});

// Team Management
Route::middleware(['web', 'auth:web,admin'])->controller(TeamController::class)->prefix('admin')->group(function () {
    // Users
    Route::group(['middleware' => 'permission:users.view|hr.staff|hr.manage'], function () {
        Route::get('/users', 'index')->name('admin.users.index');
    });
    Route::group(['middleware' => 'permission:users.create|hr.staff|hr.manage'], function () {
        Route::post('/users/store', 'storeUser')->name('admin.users.store');
    });
    Route::group(['middleware' => 'permission:users.edit|hr.staff|hr.manage'], function () {
        Route::post('/users/{id}/update', 'updateUser')->name('admin.users.update');
        Route::post('/users/{id}/toggle-approval', 'toggleApproval')->name('admin.users.toggleApproval'); // Using edit for approval
    });
    Route::group(['middleware' => 'permission:users.delete|hr.staff|hr.manage'], function () {
        Route::delete('/users/{id}/delete', 'deleteUser')->name('admin.users.delete');
    });

    Route::post('/users/bulk-action', 'bulkAction')->name('admin.users.bulkAction');

    // Roles
    Route::group(['middleware' => 'permission:roles.view|hr.roles|hr.manage'], function () {
        Route::get('/roles', 'roles')->name('admin.roles.index');
    });
    Route::group(['middleware' => 'permission:roles.create|hr.roles|hr.manage'], function () {
        Route::post('/roles/store', 'storeRole')->name('admin.roles.store');
    });
    Route::group(['middleware' => 'permission:roles.edit|hr.roles|hr.manage'], function () {
        Route::post('/roles/{id}/update', 'updateRole')->name('admin.roles.update');
    });
    Route::group(['middleware' => 'permission:roles.delete|hr.roles|hr.manage'], function () {
        Route::delete('/roles/{id}/delete', 'deleteRole')->name('admin.roles.delete');
    });
    Route::post('/roles/bulk-action', 'bulkRoleAction')->name('admin.roles.bulkAction');

    // Chat Route
    Route::get('/chat', function () {
        return view('admin.chat.index');
    })->name('admin.chat.index')->middleware('permission:chat.view');

    // Tickets
    Route::controller(TicketController::class)->prefix('tickets')->group(function () {
        Route::get('/', 'index')->name('admin.tickets.index')->middleware('permission:tickets.view');
        Route::get('/create', 'create')->name('admin.tickets.create')->middleware('permission:tickets.create');
        Route::post('/', 'store')->name('admin.tickets.store')->middleware('permission:tickets.create');
        Route::get('/{id}', 'show')->name('admin.tickets.show')->middleware('permission:tickets.view');
        Route::post('/{id}/update', 'updateStatus')->name('admin.tickets.update')->middleware('permission:tickets.edit');
        Route::post('/{id}/reply', 'storeReply')->name('admin.tickets.reply')->middleware('permission:tickets.reply');
        Route::post('/{id}/assign', 'assign')->name('admin.tickets.assign')->middleware('permission:tickets.assign');
        Route::post('/{id}/convert-to-task', 'convertToTask')->name('admin.tickets.convert_to_task')->middleware('permission:tasks.create');
    });

    // Clients
    Route::controller(AdminClientController::class)->prefix('clients')->where(['id' => '[0-9]+'])->group(function () {
        Route::get('/', 'index')->name('admin.clients.index')->middleware('permission:clients.view');
        Route::get('/create', 'create')->name('admin.clients.create')->middleware('permission:clients.create');
        Route::get('/export/csv', 'export')->name('admin.clients.export')->middleware('permission:clients.view');
        Route::post('/', 'store')->name('admin.clients.store')->middleware('permission:clients.create');
        Route::get('/{id}', 'show')->name('admin.clients.show')->middleware('permission:clients.view');
        Route::get('/{id}/edit', 'edit')->name('admin.clients.edit')->middleware('permission:clients.edit');
        Route::post('/{id}/update', 'update')->name('admin.clients.update')->middleware('permission:clients.edit');
        Route::delete('/{id}/delete', 'destroy')->name('admin.clients.delete')->middleware('permission:clients.delete');
    });

    // Client Imports
    Route::controller(ClientImportController::class)->prefix('clients/imports')->middleware('permission:clients.create')->group(function () {
        Route::get('/', 'index')->name('admin.clients.imports.index');
        Route::post('/parse', 'parse')->name('admin.clients.imports.parse');
        Route::post('/process', 'process')->name('admin.clients.imports.process');
        Route::get('/history', 'history')->name('admin.clients.imports.history');
        Route::get('/{id}/errors', 'errors')->name('admin.clients.imports.errors');
    });

    // Client Email Templates
    Route::controller(ClientEmailTemplateController::class)->prefix('clients/email-templates')->where(['id' => '[0-9]+'])->middleware('permission:clients.view')->group(function () {
        Route::get('/', 'index')->name('admin.clients.email-templates.index');
        Route::get('/create', 'create')->name('admin.clients.email-templates.create');
        Route::post('/', 'store')->name('admin.clients.email-templates.store');
        Route::post('/preview', 'preview')->name('admin.clients.email-templates.preview');
        Route::get('/{id}/edit', 'edit')->name('admin.clients.email-templates.edit');
        Route::post('/{id}/update', 'update')->name('admin.clients.email-templates.update');
        Route::delete('/{id}/delete', 'destroy')->name('admin.clients.email-templates.delete');
    });

    // Client Emails (Compose & Queue)
    Route::controller(ClientEmailController::class)->prefix('clients/emails')->middleware('permission:clients.view')->group(function () {
        Route::get('/compose', 'compose')->name('admin.clients.emails.compose');
        Route::post('/send', 'send')->name('admin.clients.emails.send');
        Route::get('/queue', 'index')->name('admin.clients.emails.queue');
        Route::get('/{id}', 'show')->name('admin.clients.emails.show');
        Route::post('/{id}/retry', 'retry')->name('admin.clients.emails.retry');
    });

    // Unmatched Emails
    Route::controller(UnmatchedEmailController::class)->prefix('clients/unmatched-emails')->middleware('permission:clients.view')->group(function () {
        Route::get('/', 'index')->name('admin.clients.unmatched.index');
        Route::post('/{id}/assign', 'assign')->name('admin.clients.unmatched.assign');
        Route::post('/{id}/ignore', 'ignore')->name('admin.clients.unmatched.ignore');
    });

    // Meetings & Calls
    Route::controller(MeetingController::class)->group(function () {
        Route::get('/meetings', 'index')->name('admin.meetings.index')->middleware('permission:meetings.view');
        Route::get('/meetings/create', 'create')->name('admin.meetings.create')->middleware('permission:meetings.create');
        Route::post('/meetings', 'store')->name('admin.meetings.store')->middleware('permission:meetings.create');
        Route::get('/meetings/{meeting:uuid}', 'show')->name('admin.meetings.show')->middleware('permission:meetings.view');
        Route::get('/meetings/{meeting:uuid}/join', 'join')->name('admin.meetings.join')->middleware('permission:meetings.join');

        Route::post('/meetings/{meeting:uuid}/accept', 'accept')->name('admin.meetings.accept')->middleware('permission:meetings.join');
        Route::post('/meetings/{meeting:uuid}/reject', 'reject')->name('admin.meetings.reject')->middleware('permission:meetings.join');
        Route::post('/meetings/{meeting:uuid}/cancel', 'cancel')->name('admin.meetings.cancel')->middleware('permission:meetings.cancel');
        Route::post('/meetings/{meeting:uuid}/end', 'end')->name('admin.meetings.end')->middleware('permission:meetings.join');
        Route::post('/meetings/{meeting:uuid}/leave', 'leave')->name('admin.meetings.leave')->middleware('permission:meetings.join');
        Route::post('/meetings/{meeting:uuid}/check-timeout', 'checkTimeout')->name('admin.meetings.check-timeout');

        Route::post('/calls/audio', 'initiateAudioCall')->name('admin.calls.audio')->middleware('permission:meetings.join');
        Route::post('/calls/video', 'initiateVideoCall')->name('admin.calls.video')->middleware('permission:meetings.join');
    });
});

// Client Support Portal
Route::middleware(['web', 'auth:client,admin'])->controller(ClientController::class)->prefix('client')->group(function () {
    Route::get('/dashboard', 'dashboard')->name('client.dashboard');
    Route::get('/tickets/create', 'create')->name('client.tickets.create');
    Route::post('/tickets', 'store')->name('client.tickets.store');
    Route::get('/tickets/{id}', 'show')->name('client.tickets.show');
    Route::post('/tickets/{id}/reply', 'reply')->name('client.tickets.reply');

    // Client Tasks
    Route::get('/tasks/{id}', 'showTask')->name('client.tasks.show');
    Route::post('/tasks/{id}/reply', 'replyTask')->name('client.tasks.reply');

    // Client Chat
    Route::get('/chat', function () {
        return view('client.chat.index');
    })->name('client.chat.index');

    // Client Profile Update
    Route::post('/profile/update', 'updateProfile')->name('client.profile.update');

    // Client Notifications
    Route::get('/notifications/{id}/read', 'readAndRedirect')->name('client.notifications.readAndRedirect');
    Route::post('/notifications/mark-as-read', 'markNotificationsRead')->name('client.notifications.markAsRead');
});

// Attendance Management
Route::middleware(['web', 'auth:web,admin'])->controller(AttendanceController::class)->prefix('admin/attendance')->group(function () {
    Route::get('/', 'dashboard')->name('admin.attendance.dashboard');
    Route::post('/clock-in', 'clockIn')->name('admin.attendance.clockIn');
    Route::post('/clock-out', 'clockOut')->name('admin.attendance.clockOut');

    // User Attendance
    Route::get('/my-daily', 'myDaily')->name('admin.attendance.myDaily');
    Route::get('/my-monthly', 'myMonthly')->name('admin.attendance.myMonthly');
    Route::post('/{id}/submit', 'submit')->name('admin.attendance.submit');
    Route::post('/{id}/resubmit', 'resubmit')->name('admin.attendance.resubmit');
    Route::post('/submit-today-report', 'submitTodayReport')->name('admin.attendance.submitTodayReport');
    Route::get('/{id}/history', 'history')->name('admin.attendance.history');

    // Super Admin / Management Routes
    Route::get('/daily', 'daily')->name('admin.attendance.daily');
    Route::get('/report-details', 'reportDetails')->name('admin.attendance.reportDetails');
    Route::post('/daily/update', 'updateDailyAttendance')->name('admin.attendance.daily.update');
    Route::patch('/{attendance}/approval-status', 'updateApprovalStatus')
    ->name('admin.attendance.updateApprovalStatus');
    Route::post('/{id}/approve', 'approve')->name('admin.attendance.approve');
    Route::post('/{id}/reject', 'reject')->name('admin.attendance.reject');
    Route::post('/bulk-approve', 'bulkApprove')->name('admin.attendance.bulkApprove');
    Route::post('/bulk-action', 'bulkAction')->name('admin.attendance.bulkAction');
    Route::get('/monthly', 'monthly')->name('admin.attendance.monthly');
    
    Route::post('/approval-status', 'changeApprovalStatus')->name('admin.attendance.changeApprovalStatus');

    Route::get('/requests', 'requests')->name('admin.attendance.requests')->middleware('permission:attendance.requests_manage|attendance.view|hr.leave_requests|hr.manage');
    Route::post('/requests', 'storeRequest')->name('admin.attendance.requests.store');
    Route::put('/requests/{id}', 'updateRequest')->name('admin.attendance.requests.update');
    Route::put('/requests/{id}/status', 'updateRequestStatus')->name('admin.attendance.requests.updateStatus')->middleware('permission:attendance.requests_manage|hr.leave_requests|hr.manage');
    Route::post('/requests/bulk-action', 'bulkRequestAction')->name('admin.attendance.requests.bulkAction')->middleware('permission:attendance.requests_manage|hr.leave_requests|hr.manage');
    Route::get('/calendar', 'calendar')->name('admin.attendance.calendar');
    Route::get('/reports', 'reports')->name('admin.attendance.reports');
    Route::get('/settings', 'settings')->name('admin.attendance.settings')->middleware('permission:attendance.settings|attendance.manage|hr.attendance_settings|hr.manage');
    Route::put('/settings', 'updateSettings')->name('admin.attendance.settings.update')->middleware('permission:attendance.settings|attendance.manage|hr.attendance_settings|hr.manage');
});


/*
|--------------------------------------------------------------------------
| Payroll Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth:web,admin'])->prefix('admin/payroll')->name('admin.payroll.')->controller(PayrollController::class)->group(function () {
    Route::get('/', 'index')->name('index');

    Route::get('/salaries', 'salaries')->name('salaries');
    Route::post('/salaries', 'storeSalary')->name('salaries.store');
    Route::post('/generate', 'generate')->name('generate');
    Route::get('/{id}', 'show')->whereNumber('id')->name('show');
    Route::post('/{id}/submit', 'submit')->whereNumber('id')->name('submit');
    Route::post('/{id}/approve', 'approve')->whereNumber('id')->name('approve');
    Route::post('/{id}/reject', 'reject')->whereNumber('id')->name('reject');
    Route::post('/{id}/mark-paid', 'markPaid')->whereNumber('id')->name('markPaid');
    Route::get('/{id}/payslip', 'payslip')->whereNumber('id')->name('payslip');
});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('holidays', [HolidayController::class, 'index'])->name('holidays.index');
    Route::post('holidays', [HolidayController::class, 'store'])->name('holidays.store');
    Route::delete('holidays/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');
});



// Consolidated Settings
Route::middleware(['web', 'auth:web,admin'])->controller(SettingsController::class)->prefix('admin/settings')->group(function () {
    Route::get('/general', 'general')->name('admin.settings.general')->middleware('permission:settings.view');
    Route::get('/hr', 'hrSettings')->name('admin.hr.settings')->middleware('permission:settings.view|hr.view|hr.manage');
    Route::get('/statuses', 'statuses')->name('admin.settings.statuses')->middleware('permission:settings.view');
    Route::get('/email', 'email')->name('admin.settings.email')->middleware('permission:settings.view');
    Route::get('/autostop', 'autostop')->name('admin.settings.autostop')->middleware('permission:settings.view|hr.shift_rules|hr.manage');

    Route::view('/chat-permissions', 'admin.settings.chat-permissions')->name('admin.settings.chat-permissions')->middleware('permission:settings.view');

    // General
    Route::post('/general', 'storeGeneral')->name('admin.settings.general.store')->middleware('permission:settings.edit');

    // Email
    Route::post('/email', 'storeEmail')->name('admin.settings.email.store')->middleware('permission:settings.edit');

    // Auto Stop Timer
    Route::post('/autostop', 'storeAutostop')->name('admin.settings.autostop.store')->middleware('permission:settings.edit|hr.shift_rules|hr.manage');

    // Statuses
    Route::post('/statuses', 'storeStatus')->name('admin.settings.status.store')->middleware('permission:settings.edit');
    Route::post('/statuses/bulk-action', 'bulkStatusAction')->name('admin.settings.status.bulkAction')->middleware('permission:settings.edit');
    Route::post('/statuses/{id}/update', 'updateStatus')->name('admin.settings.status.update')->middleware('permission:settings.edit');
    Route::delete('/statuses/{id}/delete', 'destroyStatus')->name('admin.settings.status.delete')->middleware('permission:settings.edit');

    // Tags
    Route::get('/tags', 'tags')->name('admin.settings.tags')->middleware('permission:settings.view');
    Route::post('/tags', 'storeTag')->name('admin.settings.tag.store')->middleware('permission:settings.edit');
    Route::post('/tags/bulk-action', 'bulkTagAction')->name('admin.settings.tag.bulkAction')->middleware('permission:settings.edit');
    Route::post('/tags/{id}/update', 'updateTag')->name('admin.settings.tag.update')->middleware('permission:settings.edit');
    Route::delete('/tags/{id}/delete', 'destroyTag')->name('admin.settings.tag.delete')->middleware('permission:settings.edit');
});

// Task Logs & Messaging & Time Tracking
Route::middleware(['web', 'auth:web,admin'])->controller(TaskLogController::class)->group(function () {
    Route::post('/tasks/{id}/log', 'storeLog')->name('tasks.log')->middleware('permission:tasks.view');
    Route::post('/tasks/{id}/message', 'sendMessage')->name('tasks.message')->middleware('permission:tasks.view');
    Route::post('/tasks/{id}/start-timer', 'startTime')->name('tasks.start_timer')->middleware('permission:tasks.view');
    Route::post('/tasks/{id}/stop-timer', 'stopTime')->name('tasks.stop_timer')->middleware('permission:tasks.view');
    Route::post('/tasks/{id}/progress', 'updateProgress')->name('tasks.progress')->middleware('permission:tasks.view');
});

// Email Inbound Webhook (External API)
Route::post('/webhooks/email/inbound', [InboundEmailWebhookController::class, 'handle'])->name('webhooks.email.inbound');

// Dynamic Service Worker Route
Route::get('/firebase-messaging-sw.js', function () {
    return response()->view('firebase-messaging-sw')
    ->header('Content-Type', 'application/javascript')
    ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
})->name('firebase.sw');

// Dynamic PWA Manifest Route
Route::get('/manifest.json', function () {
    $settings = Setting::whereIn('key', ['app_name', 'app_logo'])->pluck('value', 'key');
    $appName = $settings['app_name'] ?? 'TeamTasker';
    $appLogo = $settings['app_logo'] ?? null;

    $iconUrl = ($appLogo && Storage::disk('public')->exists($appLogo))
    ? asset('storage/'.$appLogo)
    : asset('icons/icon-192x192.png');

    $manifest = [
        'name' => $appName.' - Modern Collaboration Hub',
        'short_name' => $appName,
        'description' => 'WhatsApp-style Chat, Audio/Video Meetings & Task Management Platform',
        'start_url' => url('/admin/chat'),
        'scope' => url('/'),
        'display' => 'standalone',
        'background_color' => '#0b141a',
        'theme_color' => '#00a884',
        'orientation' => 'any',
        'icons' => [
            [
                'src' => $iconUrl,
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
            [
                'src' => $iconUrl,
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
        ],
    ];

    return response()->json($manifest, 200, [], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
})->name('pwa.manifest');
