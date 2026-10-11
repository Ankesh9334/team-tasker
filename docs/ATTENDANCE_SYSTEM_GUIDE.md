# Team Tasker — Attendance, Daily Approval & Monthly Reporting Guide

This document describes the architecture, database schema, lifecycle workflows, permissions, and testing for the Attendance, Daily Approval, and Monthly Reporting system in Team Tasker.

---

## 1. Core Architectural Principles

- **Single Identity Source**: Operates directly on the existing `users` table (`users.id`). No separate employee table, employee profile module, or HR master is created.
- **Strict Scope Boundaries**: Does NOT implement leave management, holidays, payroll, salaries, shifts, or GPS/biometrics.
- **Decoupled GPS**: Punch-in and punch-out operations are completely decoupled from browser geolocation and do not require location permissions.
- **Decoupled Status Concepts**:
  - `attendance_status`: `present`, `absent`, `half_day`
  - `approval_status`: `draft`, `pending`, `approved`, `rejected`

---

## 2. Database Schema

### `attendances` Table
| Column | Type | Details |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary key |
| `user_id` | BIGINT UNSIGNED | Foreign key -> `users.id` (cascade delete) |
| `attendance_date` | DATE | Attendance calendar date |
| `check_in` | TIME (nullable) | Check-in time |
| `check_out` | TIME (nullable) | Check-out time |
| `working_minutes` | INT UNSIGNED | Server-calculated total working minutes |
| `attendance_status` | ENUM | `'present'`, `'absent'`, `'half_day'` |
| `approval_status` | ENUM | `'draft'`, `'pending'`, `'approved'`, `'rejected'` |
| `remarks` | TEXT (nullable) | User remarks / notes |
| `submitted_at` | TIMESTAMP (nullable) | Timestamp of report submission |
| `approved_at` | TIMESTAMP (nullable) | Timestamp of approval |
| `approved_by` | BIGINT UNSIGNED (nullable) | Foreign key -> `users.id` (null on delete) |
| `rejection_reason` | TEXT (nullable) | Mandatory reason provided when rejected |

### `attendance_logs` (Audit Trail)
| Column | Type | Details |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary key |
| `attendance_id` | BIGINT UNSIGNED | Foreign key -> `attendances.id` (cascade delete) |
| `user_id` | BIGINT UNSIGNED (nullable) | Foreign key -> `users.id` (actor who performed action) |
| `action` | VARCHAR | Action name (e.g. `attendance.punch_in`, `attendance.approved`) |
| `description` | TEXT | Human-readable event description |
| `ip_address` | VARCHAR (nullable) | Client IP address |
| `metadata` | JSON (nullable) | Old/new values, rejection reasons, parameters |
| `created_at` | TIMESTAMP | Timestamp |

---

## 3. Lifecycle Workflows

### Daily Attendance Lifecycle
```text
Draft (Punch In/Out)
   ↓
Pending (Submitted by User)
   ↓
Approved (Approved by Super Admin) [Locked for normal users]
   ↓
OR:
Rejected (by Super Admin with mandatory reason)
   ↓
Resubmitted (User adjusts remarks & resubmits)
   ↓
Pending
   ↓
Approved
```

### Working Minutes Calculation
Working time is calculated server-side using:
```php
$workingMinutes = abs($checkIn->diffInMinutes($checkOut));
```
Overnight boundary transitions past midnight (e.g. 23:30 to 01:30) are handled safely without producing negative or incorrect minutes.

---

## 4. Permissions & Role-Based Access Control

The module uses the existing Team Tasker permission system (`config/permissions.php` & `User->hasPermission()`):

- `attendance.view`: View attendance records.
- `attendance.punch`: Punch In and Punch Out.
- `attendance.submit`: Submit daily attendance report for management approval.
- `attendance.approve`: Approve daily attendance reports (Super Admin).
- `attendance.reject`: Reject daily attendance reports with reason (Super Admin).
- `attendance.report`: Access employee monthly reports.
- `attendance.export`: Export attendance data to CSV.
- `attendance.manage`: Manage attendance settings and manual corrections.
- `attendance.requests_manage`: Manage and review leave/regularization requests.
- `hr.settings`: View and configure HR settings hub.
- `hr.staff_manage`: Manage staff directory and user profiles.
- `hr.roles_manage`: Configure roles and access permissions.
- `hr.attendance_rules`: Manage work hours and shift cutoffs.
- `hr.reports_export`: Export HR analytics and staff reports.

Super Admin automatically has full bypass access across all operations.

---

## 5. Routes

| HTTP Method | URI | Route Name | Description |
|---|---|---|---|
| `GET` | `/admin/attendance` | `admin.attendance.dashboard` | Super Admin Attendance Dashboard |
| `POST` | `/admin/attendance/clock-in` | `admin.attendance.clockIn` | Punch In endpoint |
| `POST` | `/admin/attendance/clock-out` | `admin.attendance.clockOut` | Punch Out endpoint |
| `GET` | `/admin/attendance/my-daily` | `admin.attendance.myDaily` | Normal user's today attendance |
| `GET` | `/admin/attendance/my-monthly` | `admin.attendance.myMonthly` | Normal user's monthly report & calendar |
| `POST` | `/admin/attendance/{id}/submit` | `admin.attendance.submit` | Submit daily report for approval |
| `POST` | `/admin/attendance/{id}/resubmit` | `admin.attendance.resubmit` | Resubmit corrected report |
| `GET` | `/admin/attendance/daily` | `admin.attendance.daily` | Daily Work Reports table with user/day filters |
| `GET` | `/admin/attendance/report-details` | `admin.attendance.reportDetails` | Detailed activity inspection (TimeLogs, TaskLogs, remarks) |
| `POST` | `/admin/attendance/submit-today-report` | `admin.attendance.submitTodayReport` | Global header daily work report submission |
| `POST` | `/admin/attendance/{id}/approve` | `admin.attendance.approve` | Approve daily report |
| `POST` | `/admin/attendance/{id}/reject` | `admin.attendance.reject` | Reject daily report (requires reason) |
| `POST` | `/admin/attendance/bulk-approve` | `admin.attendance.bulkApprove` | Bulk approve selected pending reports |
| `POST` | `/admin/attendance/bulk-action` | `admin.attendance.bulkAction` | Unified bulk action for daily attendance |
| `POST` | `/admin/attendance/requests/bulk-action` | `admin.attendance.requests.bulkAction` | Bulk approve / reject attendance & leave requests |
| `POST` | `/admin/roles/bulk-action` | `admin.roles.bulkAction` | Bulk safe-delete roles |
| `POST` | `/admin/attendance/daily/update` | `admin.attendance.daily.update` | Super Admin manual correction |
| `GET` | `/admin/attendance/{id}/history` | `admin.attendance.history` | Audit trail history endpoint |
| `GET` | `/admin/attendance/monthly` | `admin.attendance.monthly` | Super Admin monthly reports & team overview |
| `GET` | `/admin/attendance/reports` | `admin.attendance.reports` | Streaming CSV export endpoint |
| `GET` | `/admin/attendance/settings` | `admin.attendance.settings` | Attendance policy & office hours settings |
| `GET` | `/admin/settings/hr` | `admin.hr.settings` | Centralized HR & Staff Settings Hub |

---

## 6. Navigation Architecture

- **Attendance**:
  - Dashboard (`admin.attendance.dashboard`)
  - Daily Work Reports (`admin.attendance.daily`)
  - Monthly Attendance (`admin.attendance.monthly`)
  - My Attendance (`admin.attendance.myDaily`)
  - My Monthly Report (`admin.attendance.myMonthly`)
  - Calendar (`admin.attendance.calendar`)
  - Reports (`admin.attendance.reports`)
  - Attendance Settings (`admin.attendance.settings`)
- **HR Settings**:
  - HR Overview (`admin.hr.settings`)
  - Users & Staff (`admin.users.index`)
  - Roles & Permissions (`admin.roles.index`)
  - Leave Requests (`admin.attendance.requests`)
  - Shift & Timer Rules (`admin.settings.autostop`)
- **Settings**:
  - General (`admin.settings.general`)
  - Task Statuses (`admin.settings.statuses`)
  - Tags (`admin.settings.tags`)
  - Email Integration (`admin.settings.email`)
  - Chat Permissions (`admin.settings.chat-permissions`)

---

## 7. Testing

Run feature tests:
```bash
php artisan test --filter=AttendanceFeatureTest
```

All 24 tests verify:
- Punch In without GPS and duplicate punch prevention.
- Punch Out with server-side working minutes calculation.
- IDOR prevention (users cannot access or edit other users' reports).
- Submission and resubmission workflows.
- Super Admin approval and rejection with mandatory reason.
- Bulk approvals in database transactions.
- Super Admin manual correction with audit logging of old and new values.
- Monthly report calculations and percentage formulas.
- CSV export security and filter adherence.
- Multi-guard support (`Admin` and `User` models).
- Header Quick Actions (Icon-only Clock In/Out and global Submit Report modal).
- Daily Work Reports user & day filters and AJAX activity inspection (`TimeLog` + `TaskLog`).
- Sidebar menu separation between Attendance and HR Settings.
- Granular HR & Staff permissions assignment and verification.
- Safe bulk actions for Roles (protecting system roles) and Leave/Attendance Requests.
