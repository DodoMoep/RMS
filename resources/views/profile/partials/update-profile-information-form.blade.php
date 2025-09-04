<section>
    <header>
        <h2>{{ __('Profile Information') }}</h2>
        <p>{{ __("Update your account's profile information and email address.") }}</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div class="row mb-3">
            <label for="name" class="col-sm-2 col-form-label">Name</label>
            <div class="col-sm-10">
                <input class="form-control @error('name') is-invalid @enderror" type="text" id="name" name="name" value="{{ old('email', $user->name) }}">
                @error('name') <div id="nameFeedback" class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="row mb-3">
            <label for="email" class="col-sm-2 col-form-label">e-Mail</label>
            <div class="col-sm-10">
                <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email', $user->email) }}">
                @error('email') <div id="emailFeedback" class="invalid-feedback">{{ $message }}</div> @enderror
                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="mt-3">
                        <p class="">
                            {{ __('Your email address is unverified.') }}
                            <button form="send-verification" class="btn btn-secondary">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>
                        @if (session('status') === 'verification-link-sent')
                            <p class="alert alert-warning">{{ __('A new verification link has been sent to your email address.') }}</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="row mb-4">
            <label for="avatar" class="col-sm-2 col-form-label">Profilbild</label>
            <div class="col-sm-10">
                <input type="file" id="avatar" name="avatar" accept="image/*" class="form-control @error('avatar') is-invalid @enderror">
                @error('avatar') <div id="avatarFeedback" class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div>
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <div class="alert alert-success">{{ __('Saved.') }}</div>
            @endif
        </div>
    </form>
</section>
