<x-guest-layout>
    <p class="text-body-secondary">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success" role="alert">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div class="row mb-4">
                <div class="col-6">
                    <x-primary-button>{{ __('Resend Verification Email') }}</x-primary-button>
                </div>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <div class="row mb-4">
                <div class="col-6">
                    <x-primary-button>{{ __('Log Out') }}</x-primary-button>
                </div>
            </div>
        </form>
    </div>
</x-guest-layout>
