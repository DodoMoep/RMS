<x-app-layout>
    <h1 class="h3 mb-3">{{ __('permissions.edit') }}</h1>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('permissions.update', $permission) }}">
                @csrf @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input id="name" name="name" type="text"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $permission->name) }}">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Aktualisieren</button>
                    <a href="{{ route('permissions.index') }}" class="btn btn-outline-secondary">Abbrechen</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
