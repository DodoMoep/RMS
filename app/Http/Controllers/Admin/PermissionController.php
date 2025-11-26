<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PermissionRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('admin.permissions.create');
    }

    public function store(PermissionRequest $request)
    {
        Permission::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        \Artisan::call('permission:cache-reset');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return redirect()->route('permissions.index')->with('status', 'Berechtigung erstellt.');
    }

    public function edit(Permission $permission)
    {
        abort_unless($permission->guard_name === 'web', 404);
        return view('admin.permissions.edit', compact('permission'));
    }

    public function update(PermissionRequest $request, Permission $permission)
    {
        abort_unless($permission->guard_name === 'web', 404);

        $permission->update(['name' => $request->name]);
        \Artisan::call('permission:cache-reset');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return redirect()->route('permissions.index')->with('status', 'Berechtigung aktualisiert.');
    }

    public function destroy(Permission $permission)
    {
        abort_unless($permission->guard_name === 'web', 404);

        $permission->delete();
        \Artisan::call('permission:cache-reset');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return redirect()->route('permissions.index')->with('status', 'Berechtigung gelöscht.');
    }
}

