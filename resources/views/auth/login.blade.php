<x-guest-layout>
    <h1>Login</h1>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <p class="text-body-secondary">Sign In to your account</p>
        <div class="input-group mb-3">
            <span class="input-group-text">
                <i class="fas fa-user"></i>
            </span>
            <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Username">
            @error('email') <div id="emailFeedback" class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="input-group mb-3">
            <span class="input-group-text">
                <i class="fas fa-lock"></i>
            </span>
            <input class="form-control @error('password') is-invalid @enderror" type="password" id="password" name="password" placeholder="Password">
            @error('password') <div id="passwordFeedback" class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="form-check mb-4">
            <input class="form-check-input" type="checkbox" name="remember" value="" id="remember_me">
            <label class="form-check-label" for="remember_me">
                {{ __('Remember me') }}
            </label>
        </div>
        <div class="row">
            <div class="col-6">
                <x-primary-button>
                    Login
                </x-primary-button>
            </div>
            <div class="col-6 text-end">
                @if (Route::has('password.request'))
                    <a class="btn btn-link px-0" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>
        </div>
    </form>
</x-guest-layout>
