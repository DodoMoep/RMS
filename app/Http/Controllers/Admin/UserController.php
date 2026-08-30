<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();
        $users = User::query()
            ->when($q, fn($query) =>
            $query->where(fn($w) =>
            $w->where('name','like',"%$q%")
                ->orWhere('email','like',"%$q%")
            )
            )
            ->latest()->paginate(10)->withQueryString();

        return view('admin.users.index', compact('users','q'));
    }

    public function create()
    {
        return view('admin.users.create', [
            'roles' => Role::orderBy('name')->get(),
            'perms' => Permission::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required','string','max:255'],
            'email'      => ['required','email','max:255','unique:users,email'],
            'password'   => ['required','string','min:8'],
            'roles'      => ['array'],
            'roles.*'    => ['string', Rule::exists('roles','name')],
            'perms'      => ['array'],
            'perms.*'    => ['string', Rule::exists('permissions','name')],
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->syncRoles($data['roles'] ?? []);
        $user->syncPermissions($data['perms'] ?? []);

        activity('admin')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'roles' => $data['roles'] ?? [],
                'perms' => $data['perms'] ?? [],
                'ip'    => $request->ip(),
            ])
            ->event('user.created')
            ->log('User angelegt');

        return redirect()->route('users.index')->with('status','User angelegt.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'user'  => $user,
            'roles' => Role::orderBy('name')->get(),
            'perms' => Permission::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'       => ['required','string','max:255'],
            'email'      => ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'password'   => ['nullable','string','min:8'],
            'roles'      => ['array'],
            'roles.*'    => ['string', Rule::exists('roles','name')],
            'perms'      => ['array'],
            'perms.*'    => ['string', Rule::exists('permissions','name')],
        ]);

        // Sich selbst nicht „aussperren": optional Schutz
        if (auth()->id() === $user->id && $user->hasRole('super-admin')) {
            if (!in_array('super-admin', $data['roles'] ?? [])) {
                return back()->withErrors(['Du kannst dir nicht selbst die Super-Admin Rolle entziehen.']);
            }
        }

        $user->fill([
            'name'  => $data['name'],
            'email' => $data['email'],
        ]);
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        $user->syncRoles($data['roles'] ?? []);
        $user->syncPermissions($data['perms'] ?? []);

        activity('admin')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'roles' => $data['roles'] ?? [],
                'perms' => $data['perms'] ?? [],
                'ip'    => $request->ip(),
            ])
            ->event('user.updated')
            ->log('User aktualisiert');

        return redirect()->route('users.index')->with('status','User aktualisiert.');
    }

    public function destroy(User $user)
    {
        // Sich selbst nicht löschen
        abort_if(auth()->id() === $user->id, 403, 'Du kannst dich nicht selbst löschen.');

        activity('admin')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['ip' => request()->ip()])
            ->event('user.deleted')
            ->log('User gelöscht');

        $user->delete();
        return back()->with('status','User gelöscht.');
    }
}
