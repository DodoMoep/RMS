<x-app-layout>
    <h1 class="h3 mb-3">Rolle bearbeiten</h1>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('roles.update', $role) }}">
                @csrf @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input id="name" name="name" type="text"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $role->name) }}">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <div class="fw-semibold mb-2">{{ __('roles.permissions') }}</div>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-2">
                        @foreach($permissions as $perm)
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           name="permissions[]" value="{{ $perm->name }}"
                                           id="perm-{{ $perm->id }}"
                                        {{ in_array($perm->id, $rolePermissions) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="perm-{{ $perm->id }}">
                                        {{ $perm->name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('permissions') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Aktualisieren</button>
                    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Abbrechen</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
