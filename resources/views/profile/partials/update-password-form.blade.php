<section>
    <header>
        <h2>{{ __('Update Password') }}</h2>
        <p>{{ __('Ensure your account is using a long, random password to stay secure.') }}</p>
    </header>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="row mb-3">
            <label for="current_password" class="col-sm-2 col-form-label">{{ __('Current Password') }}</label>
            <div class="col-sm-10">
                <input class="form-control @error('current_password') is-invalid @enderror" type="password" id="current_password" name="current_password">
                @error('current_password') <div id="current_passwordFeedback" class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row mb-3">
            <label for="password" class="col-sm-2 col-form-label">{{ __('New Password') }}</label>
            <div class="col-sm-10">
                <input class="form-control @error('password') is-invalid @enderror" type="password" id="password" name="password">
                @error('password') <div id="passwordFeedback" class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row mb-4">
            <label for="password_confirmation" class="col-sm-2 col-form-label">{{ __('Confirm Password') }}</label>
            <div class="col-sm-10">
                <input class="form-control @error('current_password') is-invalid @enderror" type="password" id="password_confirmation" name="password_confirmation">
                @error('password_confirmation') <div id="password_confirmationFeedback" class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div>
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <div class="alert alert-success">{{ __('Saved.') }}</div>
            @endif
        </div>
    </form>
</section>
