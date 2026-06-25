<?php

namespace App\Http\Controllers;
use App\Models\Role;
use App\Models\Company;
use App\Models\Branch;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the employees.
     */
    public function index()
    {
        $userBranchId = \Illuminate\Support\Facades\Auth::user()->getAssignedBranchId();

        $baseQuery = Employee::query()
            ->when($userBranchId, fn($q) => $q->where('branch', Branch::query()->find($userBranchId)?->name));

        $stats = [
            'total'  => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'new'    => (clone $baseQuery)->where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        $employees = $baseQuery->with('user')->paginate(10);
        $companies = Company::query()->where('id', auth()->user()->company_id)->get();
        $branches  = Branch::query()->when($userBranchId, fn($q) => $q->where('id', $userBranchId))->get();
        return view('frontend.setting.employee', compact('employees', 'stats', 'companies', 'branches'));
    }

    public function create()
    {
        return view('frontend.setting.employee_create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'fullName' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'gender' => 'nullable|string',
            'department' => 'nullable|string',
            'designation' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'companyName' => 'required|string',
            'branch' => 'required_without:store|nullable|string',
            'store' => 'required_without:branch|nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $fullName = $request->fullName;
            
            // Handle Photo
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                $filename = date('YmdHi') . $file->getClientOriginalName();
                $file->move(public_path('upload/admin_images'), $filename);
                $photoPath = $filename;
            }

            // 1. Create Employee
            Employee::query()->create([
                'user_id' => null, // No login yet
                'employee_id' => $request->employeeId ?? 'EMP-' . date('Y') . '-' . rand(100, 999),
                'title' => $request->title,
                'full_name' => $fullName,
                'email' => $request->email,
                'photo' => $photoPath,
                'national_id' => $request->nationalId,
                'dob' => $request->dob,
                'gender' => $request->gender,
                'marital_status' => $request->marital_status,
                'phone' => $request->phone,
                'address' => $request->address,
                'district' => $request->district,
                'country' => $request->country,
                'company' => $request->companyName,
                'designation' => $request->designation,
                'department' => $request->department,
                'branch' => $request->branch,
                'store' => $request->store,
                'salary' => $request->salary,
                'status' => 'active',
            ]);

            DB::commit();
            return Redirect::route('employee.index')->with('success', 'Employee registered successfully. You can now assign login access from the User Access page.');

        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withInput()->with('error', 'Registration failed: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $employee = Employee::query()->with('user')->findOrFail($id);
        return response()->json($employee);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::query()->with('user')->findOrFail($id);
        $user = $employee->user;

        $request->validate([
            'fullName' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'gender' => 'nullable|string',
            'department' => 'nullable|string',
            'designation' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'companyName' => 'required|string',
            'branch' => 'required_without:store|nullable|string',
            'store' => 'required_without:branch|nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $fullName = $request->fullName;

            // Update Photo
            $photoPath = $employee->photo;
            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                if ($employee->photo && file_exists(public_path('upload/admin_images/' . $employee->photo))) {
                    @unlink(public_path('upload/admin_images/' . $employee->photo));
                }
                $filename = date('YmdHi') . $file->getClientOriginalName();
                $file->move(public_path('upload/admin_images'), $filename);
                $photoPath = $filename;
            }

            if ($user) {
                $user->update([
                    'name' => $fullName,
                    'email' => $request->email,
                    'photo' => $photoPath,
                ]);
            }

            $employee->update([
                'title' => $request->title,
                'full_name' => $fullName,
                'photo' => $photoPath,
                'national_id' => $request->nationalId,
                'dob' => $request->dob,
                'gender' => $request->gender,
                'marital_status' => $request->marital_status,
                'phone' => $request->phone,
                'address' => $request->address,
                'district' => $request->district,
                'country' => $request->country,
                'company' => $request->companyName,
                'designation' => $request->designation,
                'department' => $request->department,
                'branch' => $request->branch,
                'store' => $request->store,
                'salary' => $request->salary,
            ]);

            DB::commit();
            return Redirect::route('employee.index')->with('success', 'Staff member updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withInput()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $employee = Employee::query()->findOrFail($id);
        // Also delete the user if exists
        $user = User::query()->find($employee->user_id);
        if ($user) {
            if ($user->photo && file_exists(public_path('upload/admin_images/' . $user->photo))) {
                @unlink(public_path('upload/admin_images/' . $user->photo));
            }
            $user->delete();
        }
        $employee->delete();
        return Redirect::back()->with('success', 'Employee deleted successfully.');
    }

    public function assignLogin()
    {
        $employees = Employee::query()->with('user')->get();
        $roles = Role::query()->get();
        $stats = [
            'total' => $employees->count(),
            'assigned' => $employees->whereNotNull('user_id')->count(),
            'pending' => $employees->whereNull('user_id')->count(),
            'active_sessions' => 0, // Placeholder
        ];
        return view('frontend.setting.Assign_login', compact('employees', 'stats', 'roles'));
    }

    public function updateLoginAccess(Request $request, $id)
    {
        $employee = Employee::query()->findOrFail($id);
        
        $request->validate([
            'username' => 'required|string|unique:users,username,' . ($employee->user_id ?? 0),
            'email' => 'required|email|unique:users,email,' . ($employee->user_id ?? 0),
            'password' => $employee->user_id ? 'nullable|min:8' : 'required|min:8',
            'userRole' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            if ($employee->user_id) {
                // Update existing user
                $user = User::query()->findOrFail($employee->user_id);
                $userData = [
                    'username' => $request->username,
                    'email' => $request->email,
                    // An admin's role can never be changed from here — enforced
                    // server-side (not just disabled in the UI) so it can't be
                    // bypassed by editing the page or posting directly.
                    'role' => $user->role === 'admin' ? 'admin' : $request->userRole,
                ];
                if ($request->password) {
                    $userData['password'] = Hash::make($request->password);
                }
                $user->update($userData);
            } else {
                // Create new user
                $user = User::query()->create([
                    'name' => $employee->full_name,
                    'username' => $request->username,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => $request->userRole,
                    'photo' => $employee->photo,
                ]);
                $employee->update(['user_id' => $user->id]);
            }

            DB::commit();
            return Redirect::back()->with('success', 'User access configured successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->with('error', 'Failed to configure login access: ' . $e->getMessage());
        }
    }
}
