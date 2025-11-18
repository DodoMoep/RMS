<x-guest-layout>
    <p class="text-body-secondary">
        {{ __('auth_ui.forgot_password_text') }}
    </p>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div class="input-group mb-3">
            <span class="input-group-text">
                @
            </span>
            <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="{{ __('auth_ui.email') }}">
            @error('email') <div id="emailFeedback" class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="row">
            <div class="col-6">
                <button class="btn btn-primary px-4" type="submit">{{ __('auth_ui.email_password_reset_link') }}</button>
            </div>
        </div>
    </form>
</x-guest-layout>
