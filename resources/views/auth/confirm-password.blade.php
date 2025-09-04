<x-guest-layout>
    <p class="text-body-secondary">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div class="input-group mb-3">
            <span class="input-group-text">
                <i class="cil-lock-locked"></i>
            </span>
            <input class="form-control @error('password') is-invalid @enderror" type="password" id="password" name="password" placeholder="Password">
            @error('password') <div id="passwordFeedback" class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="row">
            <div class="col-6">
                <x-primary-button>
                    {{ __('Confirm') }}
                </x-primary-button>
            </div>
        </div>
    </form>
</x-guest-layout>
