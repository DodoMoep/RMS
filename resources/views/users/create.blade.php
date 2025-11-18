<x-app-layout>
    <h1 class="text-xl font-semibold mb-6">Benutzer anlegen</h1>

    <form method="POST" action="{{ route('users.store') }}" class="space-y-6 max-w-2xl">
        @csrf

        <div class="row mb-3">
            <label for="name" class="col-sm-2 col-form-label">Name</label>
            <div class="col-sm-10">
                <input class="form-control @error('name') is-invalid @enderror" type="text" id="name" name="name" value="{{ old('name') }}">
                @error('name') <div id="nameFeedback" class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row mb-3">
            <label for="email" class="col-sm-2 col-form-label">e-Mail</label>
            <div class="col-sm-10">
                <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email') }}">
                @error('email') <div id="emailFeedback" class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row mb-3">
            <label for="password" class="col-sm-2 col-form-label">Passwort</label>
            <div class="col-sm-10">
                <input class="form-control @error('password') is-invalid @enderror" type="password" id="password" name="password" value="{{ old('password') }}">
                @error('password') <div id="passwordFeedback" class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
            <div class="col">
                <fieldset>
                    <legend class="fs-6 fw-semibold mb-2">Rollen</legend>
                    <div class="vstack gap-1 overflow-auto border rounded p-2" style="max-height: 16rem;">
                        @foreach($roles as $role)
                            <label class="d-flex align-items-center gap-2">
                                <input type="checkbox" name="roles[]" value="{{ $role->name }}" class="form-check-input m-0">
                                <span>{{ $role->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('roles')" class="mt-2" />
                </fieldset>
            </div>

            <div class="col">
                <fieldset>
                    <legend class="fs-6 fw-semibold mb-2">{{ __('roles.permissions') }}</legend>
                    <div class="vstack gap-1 overflow-auto border rounded p-2" style="max-height: 16rem;">
                        @foreach($perms as $perm)
                            <label class="d-flex align-items-center gap-2">
                                <input type="checkbox" name="perms[]" value="{{ $perm->name }}" class="form-check-input m-0" >
                                <span>{{ $perm->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('perms')" class="mt-2" />
                </fieldset>
            </div>
        </div>

        <x-primary-button>Speichern</x-primary-button>

        <a href="{{ route('users.index') }}" class="btn btn-danger" role="button">Abbrechen</a>
    </form>
</x-app-layout>
