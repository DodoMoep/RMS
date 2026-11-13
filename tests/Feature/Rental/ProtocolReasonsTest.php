<?php

use App\Models\Rental\Photo;
use App\Models\Rental\Protocol;
use App\Models\Rental\Signature;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Der Grund eines Fehlers im Protokoll erreicht den Benutzer: Validierungsfehler bei Fotos,
 * die Grenze von 30 Fotos und die Gruende einer ungueltigen Unterschrift. Unerwartete
 * Fehler zeigen nur die allgemeine Meldung.
 */
beforeEach(function () {
    Storage::fake('local');
    $this->user = userWithPermissions(['protocols.view', 'protocols.create', 'protocols.edit', 'protocols.sign']);
    $this->protocol = makeRentalProtocol();
    $this->protocol->update(['notes' => 'Ausgangsnotiz']);
});

function errorsOf(): array
{
    return session('errors')->getBag('default')->all();
}

// ------------------------------------------------------------------- Fotos

test('a photo that is too large shows the validation message', function () {
    $this->actingAs($this->user)
        ->post(route('protocol.save', $this->protocol), [
            'notes' => 'Neue Notiz',
            'photos' => [UploadedFile::fake()->image('gross.jpg')->size(6000)],
        ])
        ->assertSessionHasErrors('photos.0');

    expect(errorsOf())->not->toContain(__('protocols.messages.save_error'))
        ->and($this->protocol->photos()->count())->toBe(0)
        ->and($this->protocol->refresh()->notes)->toBe('Ausgangsnotiz');
});

test('the form page renders and explains the photo validation error', function () {
    $response = $this->actingAs($this->user)
        ->from(route('protocol.form', $this->protocol))
        ->followingRedirects()
        ->post(route('protocol.save', $this->protocol), [
            'photos' => [UploadedFile::fake()->image('gross.jpg')->size(6000)],
        ]);

    // Die Meldung nennt die Grenze von 5120 KB; vorher brach die Seite mit 500 ab
    $response->assertOk()->assertSee('5120');
});

test('a file that is not an image shows the validation message', function () {
    $this->actingAs($this->user)
        ->post(route('protocol.save', $this->protocol), [
            'photos' => [UploadedFile::fake()->create('datei.pdf', 10, 'application/pdf')],
        ])
        ->assertSessionHasErrors('photos.0');

    expect(errorsOf())->not->toContain(__('protocols.messages.save_error'))
        ->and($this->protocol->photos()->count())->toBe(0);
});

test('more than 20 photos at once show the validation message', function () {
    $photos = array_map(fn ($i) => UploadedFile::fake()->image("foto{$i}.jpg"), range(1, 21));

    $this->actingAs($this->user)
        ->post(route('protocol.save', $this->protocol), ['photos' => $photos])
        ->assertSessionHasErrors('photos');

    expect($this->protocol->photos()->count())->toBe(0);
});

test('valid photos are stored', function () {
    $this->actingAs($this->user)
        ->post(route('protocol.save', $this->protocol), [
            'notes' => 'Mit Fotos',
            'photos' => [UploadedFile::fake()->image('eins.jpg'), UploadedFile::fake()->image('zwei.png')],
        ])
        ->assertSessionHasNoErrors();

    expect($this->protocol->photos()->count())->toBe(2)
        ->and($this->protocol->refresh()->notes)->toBe('Mit Fotos');
});

test('the 31st photo is rejected with the reason in the selected language', function (string $locale) {
    foreach (range(1, 29) as $i) {
        $this->protocol->photos()->create(['path' => "protocols/x/photos/{$i}.jpg", 'caption' => null]);
    }

    $this->withSession(['locale' => $locale])->actingAs($this->user)
        ->post(route('protocol.save', $this->protocol), [
            'notes' => 'Zu viele',
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])
        ->assertRedirect();

    expect(errorsOf())->toBe([__('protocols.messages.too_many_photos', ['max' => 30], $locale)])
        ->and($this->protocol->photos()->count())->toBe(29)
        ->and($this->protocol->refresh()->notes)->toBe('Ausgangsnotiz');
})->with(['de', 'en']);

test('exactly 30 photos are allowed', function () {
    foreach (range(1, 29) as $i) {
        $this->protocol->photos()->create(['path' => "protocols/x/photos/{$i}.jpg", 'caption' => null]);
    }

    $this->actingAs($this->user)
        ->post(route('protocol.save', $this->protocol), ['photos' => [UploadedFile::fake()->image('letztes.jpg')]])
        ->assertSessionHasNoErrors();

    expect($this->protocol->photos()->count())->toBe(30);
});

// -------------------------------------------------------------- Unterschrift

test('an invalid signature shows its reason', function (string $data, string $messageKey) {
    $this->actingAs($this->user)
        ->post(route('protocol.sign', $this->protocol), [
            'role' => 'tenant', 'signer' => 'Test Person', 'signature_data' => $data,
        ])
        ->assertRedirect();

    expect(errorsOf())->toBe([__($messageKey)])
        ->and($this->protocol->signatures()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'no comma' => ['keinkomma', 'protocols.messages.invalid_signature'],
    'not base64' => ['data:image/png;base64,@@@', 'protocols.messages.invalid_signature'],
    'text instead of an image' => ['data:image/png;base64,'.base64_encode('Hallo Welt'), 'protocols.messages.signature_not_png'],
    'gif instead of png' => ['data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', 'protocols.messages.signature_not_png'],
]);

test('a signature over 2 MB is rejected as too large', function () {
    $png = base64_decode(explode(',', signaturePngDataUri())[1]);
    $huge = 'data:image/png;base64,'.base64_encode($png.str_repeat("\0", 2 * 1024 * 1024 + 10));

    $this->actingAs($this->user)
        ->post(route('protocol.sign', $this->protocol), [
            'role' => 'tenant', 'signer' => 'Test Person', 'signature_data' => $huge,
        ])
        ->assertRedirect();

    expect(errorsOf())->toBe([__('protocols.messages.signature_too_large')])
        ->and($this->protocol->signatures()->count())->toBe(0);
});

test('the signature reasons exist in both languages', function (string $key) {
    expect(__("protocols.messages.{$key}", ['max' => 30], 'de'))->not->toBe("protocols.messages.{$key}")
        ->and(__("protocols.messages.{$key}", ['max' => 30], 'en'))->not->toBe("protocols.messages.{$key}")
        ->and(__("protocols.messages.{$key}", ['max' => 30], 'de'))->not->toBe(__("protocols.messages.{$key}", ['max' => 30], 'en'));
})->with(['too_many_photos', 'invalid_signature', 'signature_not_png', 'signature_too_large']);

test('missing signature fields still show their validation messages', function () {
    $this->actingAs($this->user)
        ->post(route('protocol.sign', $this->protocol), ['role' => 'tenant'])
        ->assertSessionHasErrors(['signer', 'signature_data']);
});

// ------------------------------------------------------- Unerwartete Fehler

test('an unexpected error while saving shows only the generic message and is logged', function () {
    Protocol::updating(fn () => throw new RuntimeException('geheimer Detailtext: SQLSTATE[HY000]'));
    Log::spy();

    $this->actingAs($this->user)
        ->post(route('protocol.save', $this->protocol), ['notes' => 'egal'])
        ->assertRedirect();

    expect(errorsOf())->toBe([__('protocols.messages.save_error')]);
    Log::shouldHaveReceived('error')->withArgs(
        fn ($message, $context) => $message === 'Protocol save error' && $context['exception'] instanceof RuntimeException
    )->once();
});

test('an unexpected error while signing shows only the generic message and is logged', function () {
    Signature::creating(fn () => throw new RuntimeException('geheimer Detailtext: /var/www/pfad'));
    Log::spy();

    $this->actingAs($this->user)
        ->post(route('protocol.sign', $this->protocol), [
            'role' => 'tenant', 'signer' => 'Test Person', 'signature_data' => signaturePngDataUri(),
        ])
        ->assertRedirect();

    expect(errorsOf())->toBe([__('protocols.messages.sign_error')])
        ->and(errorsOf()[0])->not->toContain('geheimer')
        ->and($this->protocol->signatures()->count())->toBe(0);
    Log::shouldHaveReceived('error')->withArgs(
        fn ($message, $context) => $message === 'Protocol signing error' && $context['exception'] instanceof RuntimeException
    )->once();
});

test('photos stay at zero when the protocol could not be saved', function () {
    Protocol::updating(fn () => throw new RuntimeException('kaputt'));

    $this->actingAs($this->user)->post(route('protocol.save', $this->protocol), [
        'notes' => 'egal',
        'photos' => [UploadedFile::fake()->image('a.jpg')],
    ]);

    expect(Photo::count())->toBe(0);
});
