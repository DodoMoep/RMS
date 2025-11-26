<x-app-layout>

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0">Benutzer</h1>
    </div>
    <div class="row mb-3">
        <div class="col-6">
            <form method="GET" class="ml-auto">
                <input name="q" value="{{ $q }}" placeholder="Suchen…" class="form-control"/>
            </form>
        </div>
        <div class="col-6 text-end">
            @can('user.add')
                <a href="{{ route('users.create') }}" class="btn btn-outline-primary">Neuer Benutzer</a>
            @endcan
        </div>
    </div>
    @if(session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('users.name') }}</th>
                        <th>{{ __('users.email') }}</th>
                        <th>{{ __('users.roles') }}</th>
                        <th>{{ __('users.permissions') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @foreach($user->roles->pluck('name') as $r)
                                <span class="badge rounded-pill text-bg-secondary">{{ $r }}</span>
                            @endforeach
                        </td>
                        <td>
                            @foreach($user->getPermissionNames() as $p)
                                <span class="badge rounded-pill text-bg-secondary">{{ $p }}</span>
                            @endforeach
                        </td>
                        <td class="text-end">
                            @can('user.edit')
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Bearbeiten</a>
                            @endcan
                            @can('user.delete')
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Benutzer wirklich löschen?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Löschen</button>
                                    </form>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">
        {{ $users->links('pagination::bootstrap-5') }}
    </div>
</x-app-layout>
