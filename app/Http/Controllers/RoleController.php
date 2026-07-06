<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class RoleController extends Controller
{
    public const MODULES = [
        'Dashboard' => ['view'],
        'Parties' => ['view', 'create', 'edit', 'delete'],
        'Branch & Store' => ['view', 'create', 'edit', 'delete'],
        'Product' => ['view', 'create', 'edit', 'delete'],
        'Purchase' => ['view', 'create', 'edit', 'delete'],
        'Sales & POS' => ['view', 'create', 'edit', 'delete'],
        'Accounting' => ['view', 'create', 'edit', 'delete'],
        'Reports' => ['view', 'export'],
        'Users' => ['view', 'create', 'edit', 'delete'],
        'System Admin' => ['view', 'create', 'edit', 'delete'],
    ];

    public function index()
    {
        $roles = Role::query()->get();
        
        if ($roles->count() === 0) {
            $defaults = [
                ['name' => 'Super Admin', 'description' => 'Complete system access with all permissions.', 'permissions' => array_combine(array_keys(self::MODULES), array_values(self::MODULES))],
                ['name' => 'Manager', 'description' => 'Branch and store management access.', 'permissions' => array_intersect_key(self::MODULES, array_flip(['Dashboard', 'Sales & POS', 'Purchase', 'Product', 'Branch & Store']))],
                ['name' => 'Staff', 'description' => 'Standard staff access for operations.', 'permissions' => array_intersect_key(self::MODULES, array_flip(['Dashboard', 'Sales & POS']))],
            ];
            
            foreach ($defaults as $def) {
                Role::query()->create($def);
            }
            $roles = Role::query()->get();
        }

        $modules = self::MODULES;
        return view('frontend.setting.role', compact('roles', 'modules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'description' => 'nullable|string',
            'permissions' => 'required|array',
        ]);

        Role::query()->create([
            'name' => $request->name,
            'description' => $request->description,
            'permissions' => $request->permissions,
        ]);

        return Redirect::back()->with('success', 'Role created successfully.');
    }

    public function update(Request $request, $id)
    {
        $role = Role::query()->findOrFail($id);

        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $id,
            'description' => 'nullable|string',
            'permissions' => 'required|array',
        ]);

        $role->update([
            'name' => $request->name,
            'description' => $request->description,
            'permissions' => $request->permissions,
        ]);

        return Redirect::back()->with('success', 'Role updated successfully.');
    }

    public function destroy($id)
    {
        $role = Role::query()->findOrFail($id);

        if (User::where('role', $role->name)->exists()) {
            return Redirect::back()->with('error', 'Cannot delete "' . $role->name . '" — it is currently assigned to one or more users. Reassign those users first.');
        }

        $role->delete();

        return Redirect::back()->with('success', 'Role deleted successfully.');
    }
}
