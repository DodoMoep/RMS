<?php

use App\Enums\ContactType;
use App\Mail\ProtocolFinalizedMail;
use App\Models\Rental\Hall;
use App\Models\Rental\Protocol;
use App\Models\Rental\Rental;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

const TEST_PNG_DATA_URI = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

function makeProtocol(string $type = 'handover', string $rentalStatus = 'scheduled'): Protocol
{
    $tenant = makeContact([
        'type' => ContactType::Tenant,
        'name' => 'Mieter Testverein',
        'email' => 'mieter@example.test',
    ]);
    $hall = Hall::create(['name' => 'Testhalle']);
    $rental = Rental::create([
        'contact_id' => $tenant->id,
        'hall_id' => $hall->id,
        'start' => now()->addDay(),
        'end' => now()->addDays(2),
        'status' => $rentalStatus,
    ]);

    return Protocol::create([
        'rental_id' => $rental->id,
        'type' => $type,
        'checklist' => ['stromzaehler' => null, 'wasserzaehler' => null],
    ]);
}

test('protocol list shows the tenant name', function () {
    $user = userWithPermissions(['protocols.view']);
    makeProtocol();

    $this->actingAs($user)
        ->get(route('protocols.index'))
        ->assertOk()
        ->assertSee('Mieter Testverein')
        ->assertSee('Testhalle');
});

test('protocol form shows the tenant name', function () {
    $user = userWithPermissions(['protocols.view', 'protocols.create']);
    $protocol = makeProtocol();

    $this->actingAs($user)
        ->get(route('protocol.form', $protocol))
        ->assertOk()
        ->assertSee('Mieter Testverein');
});

test('protocol is finalized after both parties signed', function () {
    Storage::fake('local');
    Mail::fake();
    $user = userWithPermissions(['protocols.view', 'protocols.sign']);
    $protocol = makeProtocol('handover', 'scheduled');

    $this->actingAs($user)
        ->post(route('protocol.sign', $protocol), [
            'role' => 'tenant',
            'signer' => 'Mieter Testverein',
            'signature_data' => TEST_PNG_DATA_URI,
        ])
        ->assertSessionHas('ok');

    expect($protocol->refresh()->pdf_path)->toBeNull();
    Mail::assertNothingSent();

    $this->actingAs($user)
        ->post(route('protocol.sign', $protocol), [
            'role' => 'landlord',
            'signer' => 'Vermieter Testperson',
            'signature_data' => TEST_PNG_DATA_URI,
        ])
        ->assertSessionHas('ok')
        ->assertSessionHasNoErrors();

    $protocol->refresh();

    expect($protocol->pdf_path)->not->toBeNull()
        ->and($protocol->pdf_sha256)->toHaveLength(64)
        ->and($protocol->rental->status)->toBe('active');
    Storage::disk('local')->assertExists($protocol->pdf_path);
    Mail::assertSent(ProtocolFinalizedMail::class, fn ($mail) => $mail->hasTo('mieter@example.test'));
});

test('return protocol closes the rental when finalized', function () {
    Storage::fake('local');
    Mail::fake();
    $user = userWithPermissions(['protocols.view', 'protocols.sign']);
    $protocol = makeProtocol('return', 'active');

    foreach (['tenant', 'landlord'] as $role) {
        $this->actingAs($user)->post(route('protocol.sign', $protocol), [
            'role' => $role,
            'signer' => 'Testperson',
            'signature_data' => TEST_PNG_DATA_URI,
        ]);
    }

    expect($protocol->refresh()->pdf_path)->not->toBeNull()
        ->and($protocol->rental->status)->toBe('closed');
});

test('finalized protocol cannot be signed again', function () {
    Storage::fake('local');
    $user = userWithPermissions(['protocols.view', 'protocols.sign']);
    $protocol = makeProtocol();
    $protocol->update(['pdf_path' => 'protocols/fertig.pdf']);

    $this->actingAs($user)
        ->post(route('protocol.sign', $protocol), [
            'role' => 'tenant',
            'signer' => 'Testperson',
            'signature_data' => TEST_PNG_DATA_URI,
        ])
        ->assertSessionHasErrors();

    expect($protocol->signatures()->count())->toBe(0);
});

test('protocol routes are not accessible without permission', function () {
    $protocol = makeProtocol();

    $this->actingAs(User::factory()->create())
        ->get(route('protocols.index'))
        ->assertForbidden();
});
