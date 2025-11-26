<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::where('guard_name', 'web')->orderBy('name')->get();
        return view('admin.roles.create', compact('permissions'));
    }

    public function store(RoleRequest $request)
    {
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($request->input('permissions', []));
        \Artisan::call('permission:cache-reset');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return redirect()->route('roles.index')->with('status', 'Rolle erstellt.');
    }

    public function edit(Role $role)
    {
        abort_unless($role->guard_name === 'web', 404);

        $permissions = Permission::where('guard_name', 'web')->orderBy('name')->get();
        $rolePermissions = $role->permissions()->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(RoleRequest $request, Role $role)
    {
        abort_unless($role->guard_name === 'web', 404);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->input('permissions', []));
        \Artisan::call('permission:cache-reset');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return redirect()->route('roles.index')->with('status', 'Rolle aktualisiert.');
    }

    public function destroy(Role $role)
    {
        abort_unless($role->guard_name === 'web', 404);

        // Optional: Geschützte Rollen nicht löschen
        if ($role->name == 'super-admin') {
            return back()->with('error', 'Diese Rolle ist geschützt und kann nicht gelöscht werden.');
        }

        $role->delete();
        \Artisan::call('permission:cache-reset');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return redirect()->route('roles.index')->with('status', 'Rolle gelöscht.');
    }
}

