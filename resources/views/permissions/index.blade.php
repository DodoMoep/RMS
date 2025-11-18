<x-app-layout>
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0">{{ __('permissions.title') }}</h1>
        @can('perm.add')
            <a href="{{ route('permissions.create') }}" class="btn btn-primary">{{ __('permissions.create') }}</a>
        @endcan
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th class="text-end">Aktionen</th>
                </tr>
                </thead>
                <tbody>
                @forelse($permissions as $permission)
                    <tr>
                        <td>{{ $permission->name }}</td>
                        <td class="text-end">
                            @can('perm.edit')
                            <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-sm btn-outline-secondary">Bearbeiten</a>
                            @endcan
                            @can('perm.delete')
                            <form action="{{ route('permissions.destroy', $permission) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('{{ __('permissions.messages.confirm_delete') }}')">>
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Löschen</button>
                            </form>
                                @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-center text-muted py-4">{{ __('permissions.no_permissions') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $permissions->links('pagination::bootstrap-5') }}
    </div>
</x-app-layout>
