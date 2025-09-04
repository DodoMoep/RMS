<x-app-layout>
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0">Rollen</h1>
        @can('role.add')
        <a href="{{ route('roles.create') }}" class="btn btn-outline-primary">Neue Rolle</a>
        @endcan
    </div>
    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Berechtigungen</th>
                    <th class="text-end">Aktionen</th>
                </tr>
                </thead>
                <tbody>
                @forelse($roles as $role)
                    <tr>
                        <td>{{ $role->name }}</td>
                        <td class="text-muted">
                            {{ $role->permissions->pluck('name')->join(', ') ?: '—' }}
                        </td>
                        <td class="text-end">
                            @can('role.edit')
                            <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-secondary">Bearbeiten</a>
                            @endcan
                            @can('role.delete')
                            <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Rolle wirklich löschen?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Löschen</button>
                            </form>
                                @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">Keine Rollen vorhanden.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $roles->links('pagination::bootstrap-5') }}
    </div>
</x-app-layout>
