<?php

use App\Enums\ContactType;
use App\Models\Rental\Hall;
use App\Models\Rental\InventoryItem;
use App\Models\Rental\Protocol;
use App\Models\Rental\Rental;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Erfolgsmeldungen und Sperrgruende erscheinen auf den Seiten der Vermietung (<x-flash />).
 * Vorher gab keine dieser Seiten session('ok') oder allgemeine Fehler aus.
 */
beforeEach(function () {
    $this->user = userWithPermissions([
        'halls.view', 'halls.create', 'halls.edit', 'halls.delete',
        'inventory.view', 'inventory.create', 'inventory.edit', 'inventory.delete',
        'rentals.view', 'rentals.create', 'rentals.edit', 'rentals.delete', 'rentals.handover', 'rentals.return',
        'protocols.view', 'protocols.create', 'protocols.edit', 'protocols.sign', 'protocols.pdf',
    ]);
});

function flashRental(Hall $hall): Rental
{
    return Rental::create([
        'contact_id' => makeContact(['type' => ContactType::Tenant])->id,
        'hall_id' => $hall->id,
        'start' => now()->addDay(),
        'end' => now()->addDays(2),
        'status' => 'scheduled',
    ]);
}

// ---------------------------------------------------------------- Erfolgsmeldungen

test('success messages are shown after saving a hall', function () {
    $hall = Hall::create(['name' => 'Alte Halle']);

    $this->actingAs($this->user)->followingRedirects()
        ->post(route('halls.store'), ['name' => 'Neue Halle'])
        ->assertSee(__('halls.messages.created'));

    $this->actingAs($this->user)->followingRedirects()
        ->put(route('halls.update', $hall), ['name' => 'Geaenderte Halle'])
        ->assertSee(__('halls.messages.updated'));

    $this->actingAs($this->user)->from(route('halls.index'))->followingRedirects()
        ->delete(route('halls.destroy', $hall))
        ->assertSee(__('halls.messages.deleted'));
});

test('success messages are shown after saving an inventory item', function () {
    $item = InventoryItem::create(['name' => 'Tisch']);

    $this->actingAs($this->user)->followingRedirects()
        ->post(route('inventory-items.store'), ['name' => 'Stuhl'])
        ->assertSee(__('inventory.messages.created'));

    $this->actingAs($this->user)->followingRedirects()
        ->put(route('inventory-items.update', $item), ['name' => 'Tisch neu'])
        ->assertSee(__('inventory.messages.updated'));

    $this->actingAs($this->user)->from(route('inventory-items.index'))->followingRedirects()
        ->delete(route('inventory-items.destroy', $item))
        ->assertSee(__('inventory.messages.deleted'));
});

test('success messages are shown after saving a rental', function () {
    $hall = Hall::create(['name' => 'Mietshalle']);
    $payload = [
        'contact_id' => makeContact(['type' => ContactType::Tenant])->id,
        'hall_id' => $hall->id,
        'start' => '2026-12-01 10:00',
        'end' => '2026-12-01 18:00',
        'status' => 'scheduled',
    ];

    $this->actingAs($this->user)->followingRedirects()
        ->post(route('rentals.store'), $payload)
        ->assertSee(__('rentals.messages.created'));

    $rental = Rental::firstOrFail();

    $this->actingAs($this->user)->followingRedirects()
        ->put(route('rentals.update', $rental), $payload)
        ->assertSee(__('rentals.messages.updated'));

    $this->actingAs($this->user)->from(route('rentals.index'))->followingRedirects()
        ->delete(route('rentals.destroy', $rental))
        ->assertSee(__('rentals.messages.deleted'));
});

test('the success message is shown in the selected language', function () {
    $this->actingAs($this->user)->withSession(['locale' => 'en'])->followingRedirects()
        ->post(route('halls.store'), ['name' => 'New Hall'])
        ->assertSee('Hall created.')
        ->assertDontSee('Halle angelegt.');
});

// ------------------------------------------------------------------ Sperrgruende

test('the reason is shown when a hall cannot be deleted', function () {
    $hall = Hall::create(['name' => 'Gebuchte Halle']);
    flashRental($hall);

    $this->actingAs($this->user)->from(route('halls.index'))->followingRedirects()
        ->delete(route('halls.destroy', $hall))
        ->assertSee(__('halls.messages.has_rentals'));
});

test('the reason is shown when an inventory item cannot be deleted', function () {
    $item = InventoryItem::create(['name' => 'Zugeordnet']);
    Hall::create(['name' => 'Halle mit Inventar'])->inventory()->sync([$item->id => ['quantity' => 2]]);

    $this->actingAs($this->user)->from(route('inventory-items.index'))->followingRedirects()
        ->delete(route('inventory-items.destroy', $item))
        ->assertSee(__('inventory.messages.assigned_to_halls'));
});

test('the reason is shown when a rental cannot be deleted', function () {
    $rental = flashRental(Hall::create(['name' => 'Halle']));
    Protocol::create(['rental_id' => $rental->id, 'type' => 'handover', 'checklist' => []]);

    $this->actingAs($this->user)->from(route('rentals.index'))->followingRedirects()
        ->delete(route('rentals.destroy', $rental))
        ->assertSee(__('rentals.messages.has_protocols'));
});

test('the booked hall error appears once at the field and not in the general list', function () {
    $hall = Hall::create(['name' => 'Belegte Halle']);
    $rental = flashRental($hall);

    $response = $this->actingAs($this->user)->followingRedirects()
        ->from(route('rentals.create'))
        ->post(route('rentals.store'), [
            'contact_id' => $rental->contact_id,
            'hall_id' => $hall->id,
            'start' => $rental->start->format('Y-m-d H:i'),
            'end' => $rental->end->format('Y-m-d H:i'),
            'status' => 'scheduled',
        ]);

    expect(substr_count($response->getContent(), __('rentals.messages.hall_booked')))->toBe(1);
});

// ------------------------------------------------------------------- Protokolle

test('the protocol form shows the saved message', function () {
    Storage::fake('local');
    $protocol = makeRentalProtocol();

    $this->actingAs($this->user)->from(route('protocol.form', $protocol))->followingRedirects()
        ->post(route('protocol.save', $protocol), ['notes' => 'Alles in Ordnung'])
        ->assertSee(__('protocols.messages.saved'));
});

test('the protocol form shows the signature message with the pending role', function () {
    Storage::fake('local');
    $protocol = makeRentalProtocol();

    $this->actingAs($this->user)->from(route('protocol.form', $protocol))->followingRedirects()
        ->post(route('protocol.sign', $protocol), [
            'role' => 'tenant', 'signer' => 'Test Person', 'signature_data' => signaturePngDataUri(),
        ])
        ->assertSee('Unterschrift gespeichert. Ausstehend: Vermieter.');
});

test('the protocol form shows the finalized message after the second signature', function () {
    Storage::fake('local');
    Mail::fake();
    $protocol = makeRentalProtocol();

    foreach (['tenant', 'landlord'] as $role) {
        $response = $this->actingAs($this->user)->from(route('protocol.form', $protocol))->followingRedirects()
            ->post(route('protocol.sign', $protocol), [
                'role' => $role, 'signer' => 'Test Person', 'signature_data' => signaturePngDataUri(),
            ]);
    }

    $response->assertSee(__('protocols.messages.finalized'));
});

test('the protocol form shows why a finalized protocol cannot be changed', function () {
    Storage::fake('local');
    $protocol = makeRentalProtocol();
    $protocol->update(['pdf_path' => 'protocols/done.pdf']);

    $this->actingAs($this->user)->from(route('protocol.form', $protocol))->followingRedirects()
        ->post(route('protocol.save', $protocol), ['notes' => 'zu spaet'])
        ->assertSee(__('protocols.messages.already_finalized'));

    $this->actingAs($this->user)->from(route('protocol.form', $protocol))->followingRedirects()
        ->post(route('protocol.sign', $protocol), [
            'role' => 'tenant', 'signer' => 'Test Person', 'signature_data' => signaturePngDataUri(),
        ])
        ->assertSee(__('protocols.messages.already_closed'));
});
