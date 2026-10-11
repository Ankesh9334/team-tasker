<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class AttendancePolicy
{
    /**
     * Determine whether the user can view any attendance records (e.g. daily/monthly reports for all users).
     */
    public function viewAny(Authenticatable $user): bool
    {
        return $this->isSuperAdminOrHasPermission($user, ['attendance.view', 'attendance.daily', 'attendance.monthly']);
    }

    /**
     * Determine whether the user can view the specific attendance record.
     */
    public function view(Authenticatable $user, Attendance $attendance): bool
    {
        if ($this->isSuperAdminOrHasPermission($user, ['attendance.view', 'attendance.daily', 'attendance.manage'])) {
            return true;
        }

        return $attendance->user_id === $user->getAuthIdentifier();
    }

    /**
     * Determine whether the user can create an attendance record.
     */
    public function create(Authenticatable $user): bool
    {
        return $this->isSuperAdminOrHasPermission($user, ['attendance.create', 'attendance.punch']);
    }

    /**
     * Determine whether the user can punch in/out.
     */
    public function punch(Authenticatable $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the attendance record.
     * Super Admin can correct any record.
     * Normal users cannot edit approved records and cannot edit another user's record.
     */
    public function update(Authenticatable $user, ?Attendance $attendance = null): bool
    {
        if ($this->isSuperAdminOrHasPermission($user, ['attendance.manage', 'attendance.update'])) {
            return true;
        }

        // Normal users can only edit their own draft or rejected record
        if ($attendance && $attendance->user_id === $user->getAuthIdentifier()) {
            return ! $attendance->isApproved();
        }

        return false;
    }

    /**
     * Determine whether the user can submit their attendance report for approval.
     */
    public function submit(Authenticatable $user, Attendance $attendance): bool
    {
        if ($this->isSuperAdminOrHasPermission($user, ['attendance.manage'])) {
            return true;
        }

        if ($attendance->user_id !== $user->getAuthIdentifier()) {
            return false;
        }

        // Cannot resubmit if already approved
        return ! $attendance->isApproved();
    }

    /**
     * Determine whether the user can resubmit their corrected attendance report.
     */
    public function resubmit(Authenticatable $user, Attendance $attendance): bool
    {
        if ($this->isSuperAdminOrHasPermission($user, ['attendance.manage'])) {
            return true;
        }

        if ($attendance->user_id !== $user->getAuthIdentifier()) {
            return false;
        }

        return ! $attendance->isApproved();
    }

    /**
     * Determine whether the user can approve attendance records.
     * Strictly restricted to Super Admin or users with attendance.approve permission.
     */
    public function approve(Authenticatable $user, ?Attendance $attendance = null): bool
    {
        return $this->isSuperAdminOrHasPermission($user, ['attendance.approve']);
    }

    /**
     * Determine whether the user can reject attendance records.
     * Strictly restricted to Super Admin or users with attendance.reject permission.
     */
    public function reject(Authenticatable $user, ?Attendance $attendance = null): bool
    {
        return $this->isSuperAdminOrHasPermission($user, ['attendance.reject']);
    }

    /**
     * Determine whether the user can bulk approve attendance records.
     */
    public function bulkApprove(Authenticatable $user): bool
    {
        return $this->isSuperAdminOrHasPermission($user, ['attendance.approve']);
    }

    /**
     * Determine whether the user can view monthly/daily reports of all users.
     */
    public function viewReport(Authenticatable $user): bool
    {
        return $this->isSuperAdminOrHasPermission($user, ['attendance.report', 'attendance.monthly', 'attendance.daily']);
    }

    /**
     * Determine whether the user can export attendance reports.
     */
    public function export(Authenticatable $user): bool
    {
        return $this->isSuperAdminOrHasPermission($user, ['attendance.export', 'attendance.report']);
    }

    /**
     * Determine whether the user can manage attendance settings/records.
     */
    public function manage(Authenticatable $user): bool
    {
        return $this->isSuperAdminOrHasPermission($user, ['attendance.manage', 'attendance.settings']);
    }

    /**
     * Determine whether the user can delete an attendance record.
     */
    public function delete(Authenticatable $user, Attendance $attendance): bool
    {
        return $this->isSuperAdminOrHasPermission($user, ['attendance.delete', 'attendance.manage']);
    }

    /**
     * Helper to verify super-admin, admin, or one of permissions across User and Admin models.
     */
    protected function isSuperAdminOrHasPermission(Authenticatable $user, array $permissions): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        if (method_exists($user, 'hasRole') && ($user->hasRole('super-admin') || $user->hasRole('admin'))) {
            return true;
        }

        if (isset($user->role) && $user->role && in_array($user->role->slug, ['super-admin', 'admin'])) {
            return true;
        }

        foreach ($permissions as $perm) {
            if (method_exists($user, 'hasPermission') && $user->hasPermission($perm)) {
                return true;
            }
        }

        return false;
    }
}
