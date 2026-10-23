<?php

use App\Enums\ContactType;
use App\Enums\OrderStatus;
use App\Models\Rental\Hall;
use App\Models\Rental\Protocol;
use App\Models\Rental\Rental;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

const PRIVATE_TEST_PNG = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

function makeFinalizableProtocol(): Protocol
{
    $tenant = makeContact(['type' => ContactType::Tenant, 'email' => 'mieter@example.test']);
    $rental = Rental::create([
        'contact_id' => $tenant->id,
        'hall_id' => Hall::create(['name' => 'Testhalle'])->id,
        'start' => now()->addDay(),
        'end' => now()->addDays(2),
        'status' => 'scheduled',
    ]);

    return Protocol::create([
        'rental_id' => $rental->id,
        'type' => 'handover',
        'checklist' => ['stromzaehler' => null, 'wasserzaehler' => null],
    ]);
}

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
});

// ---------------------------------------------------------------- Lieferschein

test('a generated delivery note is stored on the private disk only', function () {
    $user = userWithPermissions(['orders.print']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::PACKED]);

    $this->actingAs($user)->post(route('delivery-notes.generate', $order));

    $path = $order->refresh()->delivery_note_path;

    Storage::disk('local')->assertExists($path);
    Storage::disk('public')->assertMissing($path);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

test('the delivery note download delivers the private file', function () {
    $user = userWithPermissions(['orders.print']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::PACKED]);
    $path = $order->generateDeliveryNote();

    $this->actingAs($user)
        ->get(route('delivery-notes.download', $order))
        ->assertOk()
        ->assertDownload(basename($path));
});

test('the delivery note download requires login and permission', function () {
    $order = makeOrder(makeContact(), User::factory()->create(), ['status' => OrderStatus::PACKED]);
    $order->generateDeliveryNote();

    $this->get(route('delivery-notes.download', $order))->assertRedirect(route('login'));
    $this->actingAs(userWithPermissions(['orders.view']))
        ->get(route('delivery-notes.download', $order))
        ->assertForbidden();
});

// ------------------------------------------------------------------ Protokolle

test('protocol photos are stored on the private disk only', function () {
    $user = userWithPermissions(['protocols.view', 'protocols.edit']);
    $protocol = makeFinalizableProtocol();

    $this->actingAs($user)
        ->post(route('protocol.save', $protocol), [
            'notes' => 'Testnotiz',
            'photos' => [UploadedFile::fake()->image('foto.jpg', 40, 40)],
        ])
        ->assertSessionHas('ok');

    $photo = $protocol->photos()->firstOrFail();

    Storage::disk('local')->assertExists($photo->path);
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

test('signatures and the final protocol pdf are stored on the private disk only', function () {
    Mail::fake();
    $user = userWithPermissions(['protocols.view', 'protocols.sign']);
    $protocol = makeFinalizableProtocol();

    foreach (['tenant', 'landlord'] as $role) {
        $this->actingAs($user)->post(route('protocol.sign', $protocol), [
            'role' => $role,
            'signer' => 'Testperson',
            'signature_data' => PRIVATE_TEST_PNG,
        ]);
    }

    $protocol->refresh();

    expect($protocol->pdf_path)->not->toBeNull()
        ->and($protocol->signatures)->toHaveCount(2);
    Storage::disk('local')->assertExists($protocol->pdf_path);
    foreach ($protocol->signatures as $signature) {
        Storage::disk('local')->assertExists($signature->png_path);
    }
    expect(Storage::disk('public')->allFiles())->toBe([]);
});

test('the protocol pdf is only delivered to authorized users', function () {
    $protocol = makeFinalizableProtocol();
    $protocol->update(['pdf_path' => "protocols/{$protocol->id}/final.pdf"]);
    Storage::disk('local')->put($protocol->pdf_path, '%PDF-1.4 Testinhalt');

    $this->get(route('protocol.pdf', $protocol))->assertRedirect(route('login'));

    $this->actingAs(userWithPermissions(['protocols.view']))
        ->get(route('protocol.pdf', $protocol))
        ->assertForbidden();

    $this->actingAs(userWithPermissions(['protocols.view', 'protocols.pdf']))
        ->get(route('protocol.pdf', $protocol))
        ->assertOk();
});

test('the protocol pdf is not found while the protocol is not finalized or the file is missing', function () {
    $user = userWithPermissions(['protocols.view', 'protocols.pdf']);
    $protocol = makeFinalizableProtocol();

    $this->actingAs($user)->get(route('protocol.pdf', $protocol))->assertNotFound();

    $protocol->update(['pdf_path' => "protocols/{$protocol->id}/final.pdf"]);

    $this->actingAs($user)->get(route('protocol.pdf', $protocol))->assertNotFound();
});

// ------------------------------------------------------------- Umzugs-Befehl

test('the command moves existing files to the private disk and keeps the paths', function () {
    Storage::disk('public')->put('delivery-notes/delivery-note-ORD-2026-0001.pdf', 'Lieferschein');
    Storage::disk('public')->put('protocols/abc/final.pdf', 'Protokoll');
    Storage::disk('public')->put('protocols/abc/photos/foto.jpg', 'Foto');
    Storage::disk('public')->put('protocols/abc/sign-1.png', 'Unterschrift');

    $this->artisan('files:make-private')->assertSuccessful();

    foreach ([
        'delivery-notes/delivery-note-ORD-2026-0001.pdf' => 'Lieferschein',
        'protocols/abc/final.pdf' => 'Protokoll',
        'protocols/abc/photos/foto.jpg' => 'Foto',
        'protocols/abc/sign-1.png' => 'Unterschrift',
    ] as $path => $content) {
        Storage::disk('local')->assertExists($path);
        expect(Storage::disk('local')->get($path))->toBe($content);
        Storage::disk('public')->assertMissing($path);
    }

    expect(Storage::disk('public')->allDirectories('protocols'))->toBe([]);
});

test('the command leaves intentionally public files alone', function () {
    Storage::disk('public')->put('avatars/1/avatar.webp', 'Avatar');
    Storage::disk('public')->put('work-instructions/abc/anweisung.pdf', 'Anweisung');

    $this->artisan('files:make-private')->assertSuccessful();

    Storage::disk('public')->assertExists('avatars/1/avatar.webp');
    Storage::disk('public')->assertExists('work-instructions/abc/anweisung.pdf');
    Storage::disk('local')->assertMissing('avatars/1/avatar.webp');
});

test('the dry run changes nothing', function () {
    Storage::disk('public')->put('protocols/abc/final.pdf', 'Protokoll');

    $this->artisan('files:make-private --dry-run')->assertSuccessful();

    Storage::disk('public')->assertExists('protocols/abc/final.pdf');
    Storage::disk('local')->assertMissing('protocols/abc/final.pdf');
});

test('the command can run repeatedly', function () {
    Storage::disk('public')->put('protocols/abc/final.pdf', 'Protokoll');

    $this->artisan('files:make-private')->assertSuccessful();
    $this->artisan('files:make-private')
        ->expectsOutputToContain('Verschoben: 0')
        ->assertSuccessful();

    Storage::disk('local')->assertExists('protocols/abc/final.pdf');
});

test('a public copy of an already moved file is removed', function () {
    Storage::disk('local')->put('protocols/abc/final.pdf', 'Protokoll');
    Storage::disk('public')->put('protocols/abc/final.pdf', 'Protokoll');

    $this->artisan('files:make-private')->assertSuccessful();

    Storage::disk('public')->assertMissing('protocols/abc/final.pdf');
    Storage::disk('local')->assertExists('protocols/abc/final.pdf');
});

test('a conflicting file is neither overwritten nor deleted', function () {
    Storage::disk('local')->put('protocols/abc/final.pdf', 'Private Fassung');
    Storage::disk('public')->put('protocols/abc/final.pdf', 'Oeffentliche Fassung, anders lang');

    $this->artisan('files:make-private')->assertFailed();

    expect(Storage::disk('local')->get('protocols/abc/final.pdf'))->toBe('Private Fassung');
    Storage::disk('public')->assertExists('protocols/abc/final.pdf');
});
