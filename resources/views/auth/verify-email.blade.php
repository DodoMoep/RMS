<x-guest-layout>
    <p class="text-body-secondary">
        {{ __('auth_ui.verify_email_text') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success" role="alert">
            {{ __('auth_ui.verification_sent') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div class="row mb-4">
                <div class="col-6">
                    <x-primary-button>{{ __('auth_ui.resend_verification_email') }}</x-primary-button>
                </div>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <div class="row mb-4">
                <div class="col-6">
                    <x-primary-button>{{ __('auth_ui.log_out') }}</x-primary-button>
                </div>
            </div>
        </form>
    </div>
</x-guest-layout>
