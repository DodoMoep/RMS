<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="input-group mb-3">
            <span class="input-group-text">
                <i class="cil-user"></i>
            </span>
            <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email', $request->email) }}" placeholder="{{ __('auth_ui.email') }}">
            @error('email') <div id="emailFeedback" class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="input-group mb-3">
            <span class="input-group-text">
                <i class="cil-lock-locked"></i>
            </span>
            <input class="form-control @error('password') is-invalid @enderror" type="password" id="password" name="password" placeholder="{{ __('auth_ui.password') }}">
            @error('password') <div id="passwordFeedback" class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="input-group mb-4">
            <span class="input-group-text">
                <i class="cil-lock-locked"></i>
            </span>
            <input class="form-control @error('password_confirmation') is-invalid @enderror" type="password" id="password_confirmation" name="password_confirmation" placeholder="{{ __('auth_ui.confirm_password') }}">
            @error('password_confirmation') <div id="password_confirmationFeedback" class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="row">
            <div class="col-6">
                <x-primary-button>{{ __('auth_ui.reset_password') }}</x-primary-button>
            </div>
        </div>
    </form>
</x-guest-layout>
