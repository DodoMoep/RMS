<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
});

test('email can be verified', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
});

test('email is not verified with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user)->get($verificationUrl);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('the verification screen shows a sentence after the link was sent again', function (string $locale, string $sentence) {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $response = $this->withSession(['locale' => $locale])->actingAs($user)
        ->from('/verify-email')
        ->followingRedirects()
        ->post(route('verification.send'));

    $response->assertSee($sentence)->assertDontSee('auth_ui.verification_sent');
})->with([
    'german' => ['de', 'Ein neuer Verifizierungslink wurde an Ihre E-Mail-Adresse gesendet.'],
    'english' => ['en', 'A new verification link has been sent to your email address.'],
]);
