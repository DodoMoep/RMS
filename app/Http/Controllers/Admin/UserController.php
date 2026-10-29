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

        $user = new User([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        // Der Administrator legt das Konto an und bestaetigt damit die Adresse; ohne diesen
        // Wert wuerde die Middleware "verified" den neuen Benutzer von allen Seiten aussperren.
        $user->email_verified_at = now();
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
            ->event('user.created')
            ->log('User angelegt');

        return redirect()->route('users.index')->with('status', __('users.messages.created'));
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
                return back()->withErrors([__('users.messages.cannot_remove_own_super_admin')]);
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

        return redirect()->route('users.index')->with('status', __('users.messages.updated'));
    }

    /**
     * Konto aktivieren oder deaktivieren. Ein deaktiviertes Konto bleibt mit allen
     * Verknuepfungen (Auftraege, Historie) erhalten, kann sich aber nicht anmelden.
     */
    public function toggleStatus(User $user)
    {
        // Sich selbst nicht aussperren
        abort_if(auth()->id() === $user->id, 403, __('users.messages.cannot_deactivate_self'));

        if ($user->is_active && $this->isLastActiveSuperAdmin($user)) {
            return back()->withErrors([__('users.messages.cannot_deactivate_last_super_admin')]);
        }

        $active = ! $user->is_active;

        $user->is_active = $active;
        if (! $active) {
            // "Angemeldet bleiben" soll nach der Deaktivierung nicht mehr greifen
            $user->remember_token = null;
        }
        $user->save();

        activity('admin')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['ip' => request()->ip()])
            ->event($active ? 'user.activated' : 'user.deactivated')
            ->log($active ? 'User aktiviert' : 'User deaktiviert');

        return back()->with('status', __($active ? 'users.messages.activated' : 'users.messages.deactivated'));
    }

    /**
     * Es muss immer mindestens ein aktiver Super-Admin uebrig bleiben.
     */
    private function isLastActiveSuperAdmin(User $user): bool
    {
        return $user->hasRole('super-admin')
            && User::role('super-admin')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->doesntExist();
    }

    public function destroy(User $user)
    {
        // Sich selbst nicht löschen
        abort_if(auth()->id() === $user->id, 403, __('users.messages.cannot_delete_self'));

        activity('admin')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['ip' => request()->ip()])
            ->event('user.deleted')
            ->log('User gelöscht');

        $user->delete();
        return back()->with('status', __('users.messages.deleted'));
    }
}
