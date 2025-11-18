<x-guest-layout>
    <p class="text-body-secondary">
        {{ __('auth_ui.confirm_password_text') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div class="input-group mb-3">
            <span class="input-group-text">
                <i class="cil-lock-locked"></i>
            </span>
            <input class="form-control @error('password') is-invalid @enderror" type="password" id="password" name="password" placeholder="{{ __('auth_ui.password') }}">
            @error('password') <div id="passwordFeedback" class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="row">
            <div class="col-6">
                <x-primary-button>
                    {{ __('auth_ui.confirm_password') }}
                </x-primary-button>
            </div>
        </div>
    </form>
</x-guest-layout>
