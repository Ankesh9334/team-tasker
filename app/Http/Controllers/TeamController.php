<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\SalaryService;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['role', 'currentSalary']);

        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%'.$request->email.'%');
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->filled('status')) {
            $status = $request->status === 'approved';
            $query->where('is_approved', $status);
        }

        if ($request->filled('created_at')) {
            $query->whereDate('created_at', $request->created_at);
        }

        if ($request->filled('updated_at')) {
            $query->whereDate('updated_at', $request->updated_at);
        }

        $users = $query->paginate(request('per_page', 15));
        $roles = Role::all();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function storeUser(Request $request, SalaryService $salaries)
    {
        $request->validate(array_merge([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users|unique:clients',
            'password' => 'required|string|min:8',
            'role_id' => 'nullable|exists:roles,id',
        ], $this->employeeRules()));

        $user = User::create(array_merge([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role_id' => $request->role_id,
            'is_approved' => true, // Manually created users by admin should be approved by default
        ], $this->employeeData($request)));

        $this->saveSalaryIfProvided($request, $user, $salaries);

        return back()->with('success', 'User created successfully and approved.');
    }

    public function updateUser(Request $request, $id, SalaryService $salaries)
    {
        $user = User::findOrFail($id);

        $request->validate(array_merge([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id.'|unique:clients',
            'role_id' => 'nullable|exists:roles,id',
        ], $this->employeeRules($user->id)));

        $data = array_merge([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
        ], $this->employeeData($request));

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);

        $this->saveSalaryIfProvided($request, $user, $salaries);

        return back()->with('success', 'User updated successfully.');
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete yourself.');
        }
        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }

    public function toggleApproval($id)
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot change your own approval status.');
        }
        $user->update(['is_approved' => ! $user->is_approved]);
        $status = $user->is_approved ? 'approved' : 'disapproved';

        return back()->with('success', "User successfully {$status}.");
    }

    public function bulkAction(Request $request)
    {
        $ids = $request->ids;
        $action = $request->action;

        if (empty($ids)) {
            return back()->with('error', 'No users selected.');
        }

        switch ($action) {
            case 'approve':
            if (! auth()->user()->hasPermission('users.approve')) {
                abort(403, 'Unauthorized action.');
            }
            User::whereIn('id', $ids)->update(['is_approved' => true]);
            $message = 'Selected users approved successfully.';
            break;
            case 'disapprove':
            if (! auth()->user()->hasPermission('users.approve')) {
                abort(403, 'Unauthorized action.');
            }
                // Prevent self-disapproval in bulk
            User::whereIn('id', $ids)->where('id', '!=', auth()->id())->update(['is_approved' => false]);
            $message = 'Selected users disapproved successfully.';
            break;
            case 'delete':
            if (! auth()->user()->hasPermission('users.delete')) {
                abort(403, 'Unauthorized action.');
            }
            User::whereIn('id', $ids)->where('id', '!=', auth()->id())->delete();
            $message = 'Selected users deleted successfully.';
            break;
            case 'change_role':
            if (! auth()->user()->hasPermission('users.edit')) {
                abort(403, 'Unauthorized action.');
            }
            $roleId = $request->role_id;
            if (! $roleId) {
                return back()->with('error', 'Please select a role.');
            }
            User::whereIn('id', $ids)->update(['role_id' => $roleId]);
            $message = 'Roles updated for selected users.';
            break;
            default:
            return back()->with('error', 'Invalid action.');
        }

        return back()->with('success', $message);
    }

    public function roles()
    {
        $query = Role::withCount('users');

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $roles = $query->latest('id')->paginate(request('per_page', 15))->withQueryString();

        return view('admin.roles.index', compact('roles'));
    }

    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles',
            'permissions' => 'nullable|array',
        ]);

        Role::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'permissions' => $request->input('permissions', []),
        ]);

        return back()->with('success', 'Role created successfully.');
    }

    public function updateRole(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug,'.$role->id,
            'permissions' => 'nullable|array',
        ]);

        $role->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'permissions' => $request->input('permissions', []),
        ]);

        return back()->with('success', 'Role updated successfully.');
    }

    public function deleteRole($id)
    {
        $role = Role::findOrFail($id);
        if ($role->users()->count() > 0) {
            return back()->with('error', 'Cannot delete role assigned to users.');
        }
        $role->delete();

        return back()->with('success', 'Role deleted successfully.');
    }

    public function bulkRoleAction(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return back()->with('error', 'No roles selected.');
        }

        $action = $request->input('action');
        if ($action === 'delete') {
            if (! auth()->user()->hasRole('super-admin') && ! auth()->user()->hasPermission('roles.delete')) {
                abort(403, 'Unauthorized action.');
            }

            $protectedSlugs = ['super-admin', 'manager', 'client', 'hr-manager'];
            $roles = Role::whereIn('id', $ids)
            ->whereNotIn('slug', $protectedSlugs)
            ->doesntHave('users')
            ->get();

            $deletedCount = $roles->count();
            if ($deletedCount === 0) {
                return back()->with('error', 'None of the selected roles could be deleted (they may be protected or assigned to users).');
            }

            Role::whereIn('id', $roles->pluck('id'))->delete();

            return back()->with('success', "{$deletedCount} role(s) deleted successfully.");
        }

        return back()->with('error', 'Invalid action.');
    }

    // ------------------------------------------------------------ helpers

    private function employeeRules(?int $ignoreId = null): array
    {
        return [
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'address' => 'nullable|string|max:1000',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',

            'employee_code' => 'nullable|string|max:50|unique:users,employee_code'.($ignoreId ? ','.$ignoreId : ''),
            'designation' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'joining_date' => 'nullable|date',

            'pan_number' => ['nullable', 'regex:/^[A-Za-z]{5}[0-9]{4}[A-Za-z]$/'],
            'aadhaar_number' => 'nullable|digits:12',

            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|digits_between:6,20',
            'bank_ifsc' => ['nullable', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/'],

            // Salary (optional, par bharo to teeno zaroori)
            'monthly_salary' => 'nullable|numeric|min:0.01|max:9999999999.99',
            'working_days' => 'required_with:monthly_salary|array|min:1',
            'working_days.*' => 'integer|between:1,7',
            'effective_from' => 'required_with:monthly_salary|nullable|date',
            'salary_notes' => 'nullable|string|max:2000',
        ];
    }

    private function employeeData(Request $request): array
    {
        $data = $request->only([
            'phone', 'date_of_birth', 'gender', 'address',
            'emergency_contact_name', 'emergency_contact_phone',
            'employee_code', 'designation', 'department', 'joining_date',
            'aadhaar_number', 'bank_name', 'bank_account_number',
        ]);

        $data['pan_number'] = $request->filled('pan_number') ? strtoupper($request->pan_number) : null;
        $data['bank_ifsc'] = $request->filled('bank_ifsc') ? strtoupper($request->bank_ifsc) : null;

        // Khaali string ko null banao (employee_code unique hai, '' do baar save nahi ho sakta)
        foreach ($data as $key => $value) {
            if ($value === '') {
                $data[$key] = null;
            }
        }

        return $data;
    }

    private function saveSalaryIfProvided(Request $request, User $user, SalaryService $salaries): void
    {
        if (! $request->filled('monthly_salary')) {
            return;
        }

        // Salary badalne ki permission sirf payroll wale ko
        $me = auth()->user();
        if (! ($me->hasRole('super-admin') || $me->hasPermission('payroll.salary_manage'))) {
            return;
        }

        $days = array_map('intval', (array) $request->working_days);
        sort($days);

        // Kuch badla nahi to naya record mat banao
        $current = $user->currentSalary;
        if ($current) {
            $currentDays = array_map('intval', (array) $current->working_days);
            sort($currentDays);

            if ((float) $current->monthly_salary === (float) $request->monthly_salary
                && $currentDays === $days) {
                return;
        }
    }

    $salaries->setSalary($user->id, [
        'monthly_salary' => $request->monthly_salary,
        'working_days' => $days,
        'effective_from' => $request->effective_from,
        'notes' => $request->salary_notes,
    ]);
}
}