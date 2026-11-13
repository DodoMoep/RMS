<?php

use App\Enums\ContactType;
use App\Models\Rental\Hall;
use App\Models\Rental\InventoryItem;
use App\Models\Rental\Protocol;
use App\Models\Rental\Rental;
use Illuminate\Support\Facades\Log;

/**
 * Vermietungen (RentalController): Anlegen, Aendern, Loeschen und Protokolle aus der Vermietung.
 * Die Pruefung auf Ueberschneidungen steht in RentalOverlapTest.
 */
function rentalPayload(Hall $hall, array $overrides = []): array
{
    return array_merge([
        'contact_id' => makeContact(['type' => ContactType::Tenant])->id,
        'hall_id' => $hall->id,
        'start' => '2026-12-01 10:00',
        'end' => '2026-12-01 18:00',
        'price' => '450.00',
        'deposit' => '200',
        'status' => 'scheduled',
    ], $overrides);
}

function createRental(Hall $hall, array $overrides = []): Rental
{
    return Rental::create(array_merge([
        'contact_id' => makeContact(['type' => ContactType::Tenant])->id,
        'hall_id' => $hall->id,
        'start' => '2026-12-05 10:00',
        'end' => '2026-12-05 18:00',
        'status' => 'scheduled',
    ], $overrides));
}

beforeEach(function () {
    $this->hall = Hall::create(['name' => 'Testhalle']);
    $this->user = userWithPermissions(['rentals.view', 'rentals.create', 'rentals.edit', 'rentals.delete', 'rentals.handover', 'rentals.return']);
});

// ---------------------------------------------------------------- Anlegen

test('a rental can be created with all fields', function () {
    $payload = rentalPayload($this->hall);

    $this->actingAs($this->user)
        ->post(route('rentals.store'), $payload)
        ->assertRedirect(route('rentals.index'))
        ->assertSessionHas('ok', __('rentals.messages.created'));

    $rental = Rental::firstOrFail();

    expect($rental->contact_id)->toBe($payload['contact_id'])
        ->and($rental->hall_id)->toBe($this->hall->id)
        ->and($rental->start->format('Y-m-d H:i'))->toBe('2026-12-01 10:00')
        ->and($rental->end->format('Y-m-d H:i'))->toBe('2026-12-01 18:00')
        ->and((float) $rental->price)->toBe(450.0)
        ->and((float) $rental->deposit)->toBe(200.0)
        ->and($rental->status)->toBe('scheduled');
});

test('price and deposit are optional', function () {
    $this->actingAs($this->user)
        ->post(route('rentals.store'), rentalPayload($this->hall, ['price' => null, 'deposit' => null]))
        ->assertSessionHasNoErrors();

    expect(Rental::firstOrFail()->price)->toBeNull();
});

test('every rental status is accepted', function (string $status) {
    $this->actingAs($this->user)
        ->post(route('rentals.store'), rentalPayload($this->hall, ['status' => $status]))
        ->assertSessionHasNoErrors();

    expect(Rental::firstOrFail()->status)->toBe($status);
})->with(['scheduled', 'active', 'closed', 'cancelled']);

test('invalid rental data is rejected', function (array $overrides, string $errorField) {
    $this->actingAs($this->user)
        ->post(route('rentals.store'), rentalPayload($this->hall, $overrides))
        ->assertSessionHasErrors($errorField);

    expect(Rental::count())->toBe(0);
})->with([
    'no contact' => [['contact_id' => null], 'contact_id'],
    'contact not a uuid' => [['contact_id' => '12'], 'contact_id'],
    'unknown contact' => [['contact_id' => '00000000-0000-0000-0000-000000000000'], 'contact_id'],
    'no hall' => [['hall_id' => null], 'hall_id'],
    'unknown hall' => [['hall_id' => '00000000-0000-0000-0000-000000000000'], 'hall_id'],
    'no start' => [['start' => null], 'start'],
    'start not a date' => [['start' => 'morgen'], 'start'],
    'no end' => [['end' => null], 'end'],
    'end equals start' => [['start' => '2026-12-01 10:00', 'end' => '2026-12-01 10:00'], 'end'],
    'end before start' => [['start' => '2026-12-01 10:00', 'end' => '2026-12-01 09:00'], 'end'],
    'negative price' => [['price' => '-1'], 'price'],
    'price not a number' => [['price' => 'teuer'], 'price'],
    'negative deposit' => [['deposit' => '-5'], 'deposit'],
    'no status' => [['status' => null], 'status'],
    'unknown status' => [['status' => 'verloren'], 'status'],
]);

// -------------------------------------------------------------- Bearbeiten

test('a rental can be changed', function () {
    $rental = createRental($this->hall);
    $otherHall = Hall::create(['name' => 'Zweite Halle']);

    $this->actingAs($this->user)
        ->put(route('rentals.update', $rental), rentalPayload($otherHall, [
            'contact_id' => $rental->contact_id,
            'status' => 'active',
            'price' => '99',
        ]))
        ->assertRedirect(route('rentals.index'))
        ->assertSessionHas('ok', __('rentals.messages.updated'));

    $rental->refresh();

    expect($rental->hall_id)->toBe($otherHall->id)
        ->and($rental->status)->toBe('active')
        ->and((float) $rental->price)->toBe(99.0);
});

test('invalid data is rejected when changing a rental', function () {
    $rental = createRental($this->hall);

    $this->actingAs($this->user)
        ->put(route('rentals.update', $rental), rentalPayload($this->hall, [
            'contact_id' => $rental->contact_id,
            'start' => '2026-12-05 18:00',
            'end' => '2026-12-05 10:00',
        ]))
        ->assertSessionHasErrors('end');

    expect($rental->refresh()->start->format('H:i'))->toBe('10:00');
});

// ----------------------------------------------------------------- Loeschen

test('a rental without protocols is deleted', function () {
    $rental = createRental($this->hall);

    $this->actingAs($this->user)
        ->delete(route('rentals.destroy', $rental))
        ->assertSessionHas('ok', __('rentals.messages.deleted'));

    expect(Rental::find($rental->id))->toBeNull();
});

test('a rental with a protocol cannot be deleted', function () {
    $rental = createRental($this->hall);
    Protocol::create(['rental_id' => $rental->id, 'type' => 'handover', 'checklist' => []]);

    $this->actingAs($this->user)->delete(route('rentals.destroy', $rental))->assertRedirect();

    expect(session('errors')->first())->toBe(__('rentals.messages.has_protocols'))
        ->and(Rental::find($rental->id))->not->toBeNull();
});

// ------------------------------------------------------------- Liste, Formulare

test('the rental list shows tenant, hall and status', function () {
    $tenant = makeContact(['type' => ContactType::Tenant, 'name' => 'Sportverein Beispiel']);
    createRental($this->hall, ['contact_id' => $tenant->id, 'status' => 'active']);

    $this->actingAs($this->user)
        ->get(route('rentals.index'))
        ->assertOk()
        ->assertSee('Sportverein Beispiel')
        ->assertSee('Testhalle')
        ->assertSee('aktiv');
});

test('the rental list offers opening or creating the handover', function () {
    $without = createRental($this->hall, ['start' => '2026-12-05 10:00', 'end' => '2026-12-05 12:00']);

    $this->actingAs($this->user)->get(route('rentals.index'))->assertSee('Übergabe anlegen');

    Protocol::create(['rental_id' => $without->id, 'type' => 'handover', 'checklist' => []]);

    $this->actingAs($this->user)->get(route('rentals.index'))->assertSee('Übergabe öffnen');
});

test('the rental list is paginated with 15 rentals per page', function () {
    foreach (range(1, 16) as $i) {
        createRental($this->hall, ['start' => now()->addDays($i * 2), 'end' => now()->addDays($i * 2)->addHours(2)]);
    }

    $first = $this->actingAs($this->user)->get(route('rentals.index'));
    $second = $this->actingAs($this->user)->get(route('rentals.index', ['page' => 2]));

    expect($first->viewData('rentals'))->toHaveCount(15)
        ->and($second->viewData('rentals'))->toHaveCount(1);
});

test('the rental forms only offer tenants and halls', function () {
    makeContact(['type' => ContactType::Tenant, 'name' => 'Mieter Nur']);
    makeContact(['type' => ContactType::Both, 'name' => 'Beides Kontakt']);
    makeContact(['type' => ContactType::Customer, 'name' => 'Kunde Nur']);
    Hall::create(['name' => 'Auswahlhalle']);
    $rental = createRental($this->hall);

    foreach ([route('rentals.create'), route('rentals.edit', $rental)] as $url) {
        $this->actingAs($this->user)->get($url)
            ->assertOk()
            ->assertSee('Mieter Nur')
            ->assertSee('Beides Kontakt')
            ->assertDontSee('Kunde Nur')
            ->assertSee('Auswahlhalle')
            ->assertSee('Testhalle');
    }
});

// ------------------------------------------------------------------ Uebergabe

test('opening the handover creates a protocol with the inventory of the hall', function () {
    $tables = InventoryItem::create(['name' => 'Tisch']);
    $chairs = InventoryItem::create(['name' => 'Stuhl']);
    $this->hall->inventory()->sync([$tables->id => ['quantity' => 12], $chairs->id => ['quantity' => 80]]);
    $rental = createRental($this->hall);

    $response = $this->actingAs($this->user)->post(route('rentals.handover', $rental));

    $protocol = Protocol::firstOrFail();
    $response->assertRedirect(route('protocol.form', $protocol));

    expect($protocol->rental_id)->toBe($rental->id)
        ->and($protocol->type)->toBe('handover')
        ->and($protocol->checklist)->toBe(['stromzaehler' => null, 'wasserzaehler' => null])
        ->and($protocol->items->pluck('label')->sort()->values()->all())->toBe(['Stuhl (Soll: 80)', 'Tisch (Soll: 12)'])
        ->and($protocol->items->pluck('state')->unique()->all())->toBe(['ok'])
        ->and($protocol->items->pluck('inventory_item_id')->sort()->values()->all())
        ->toBe(collect([$tables->id, $chairs->id])->sort()->values()->all());
});

test('opening the handover again opens the same protocol', function () {
    $this->hall->inventory()->sync([InventoryItem::create(['name' => 'Tisch'])->id => ['quantity' => 3]]);
    $rental = createRental($this->hall);

    $this->actingAs($this->user)->post(route('rentals.handover', $rental));
    $protocol = Protocol::firstOrFail();

    $this->actingAs($this->user)
        ->post(route('rentals.handover', $rental))
        ->assertRedirect(route('protocol.form', $protocol));

    expect(Protocol::count())->toBe(1)
        ->and($protocol->items()->count())->toBe(1);
});

test('a hall without inventory gives an empty handover protocol', function () {
    $rental = createRental($this->hall);

    $this->actingAs($this->user)->post(route('rentals.handover', $rental))->assertRedirect();

    expect(Protocol::firstOrFail()->items)->toHaveCount(0);
});

test('a failing handover rolls back and shows the generic error', function () {
    $rental = createRental($this->hall);
    Protocol::creating(fn () => throw new RuntimeException('Datenbank kaputt'));
    Log::spy();

    $this->actingAs($this->user)->post(route('rentals.handover', $rental))->assertRedirect();

    expect(session('errors')->first())->toBe(__('rentals.messages.handover_error'))
        ->and(session('errors')->first())->not->toContain('kaputt')
        ->and(Protocol::count())->toBe(0);
    Log::shouldHaveReceived('error')->withArgs(fn ($message) => $message === 'Error creating handover protocol')->once();
});

// ------------------------------------------------------------------ Rueckgabe

test('the return protocol copies checklist and items of the handover', function () {
    $tables = InventoryItem::create(['name' => 'Tisch']);
    $this->hall->inventory()->sync([$tables->id => ['quantity' => 4]]);
    $rental = createRental($this->hall);

    $this->actingAs($this->user)->post(route('rentals.handover', $rental));
    $handover = Protocol::where('type', 'handover')->firstOrFail();
    $handover->update(['checklist' => ['stromzaehler' => '1234', 'wasserzaehler' => '56']]);
    $handover->items()->update(['state' => 'damaged']);

    $response = $this->actingAs($this->user)->post(route('rentals.return', $rental));

    $return = Protocol::where('type', 'return')->firstOrFail();
    $response->assertRedirect(route('protocol.form', $return));

    // Die Checkliste wird uebernommen, der Zustand der Positionen beginnt wieder bei "ok"
    expect($return->checklist)->toBe(['stromzaehler' => '1234', 'wasserzaehler' => '56'])
        ->and($return->items)->toHaveCount(1)
        ->and($return->items[0]->label)->toBe('Tisch (Soll: 4)')
        ->and($return->items[0]->inventory_item_id)->toBe($tables->id)
        ->and($return->items[0]->state)->toBe('ok')
        ->and($handover->items()->first()->state)->toBe('damaged');
});

test('the return protocol creates a handover when there is none', function () {
    $rental = createRental($this->hall);

    $this->actingAs($this->user)->post(route('rentals.return', $rental))->assertRedirect();

    expect(Protocol::where('type', 'handover')->count())->toBe(1)
        ->and(Protocol::where('type', 'return')->count())->toBe(1)
        ->and(Protocol::where('type', 'return')->firstOrFail()->checklist)->toBe([]);
});

test('opening the return again opens the same protocol', function () {
    $rental = createRental($this->hall);

    $this->actingAs($this->user)->post(route('rentals.return', $rental));
    $return = Protocol::where('type', 'return')->firstOrFail();

    $this->actingAs($this->user)
        ->post(route('rentals.return', $rental))
        ->assertRedirect(route('protocol.form', $return));

    expect(Protocol::count())->toBe(2);
});

test('a failing return rolls back and shows the generic error', function () {
    $rental = createRental($this->hall);
    Protocol::creating(fn () => throw new RuntimeException('Datenbank kaputt'));
    Log::spy();

    $this->actingAs($this->user)->post(route('rentals.return', $rental))->assertRedirect();

    expect(session('errors')->first())->toBe(__('rentals.messages.return_error'))
        ->and(session('errors')->first())->not->toContain('kaputt')
        ->and(Protocol::count())->toBe(0);
    Log::shouldHaveReceived('error')->withArgs(fn ($message) => $message === 'Error creating return protocol')->once();
});

// -------------------------------------------------------------------- Rechte

test('every rental action needs its own permission', function () {
    $rental = createRental($this->hall);
    $viewer = userWithPermissions(['rentals.view']);

    $this->actingAs($viewer)->get(route('rentals.index'))->assertOk();
    $this->actingAs($viewer)->get(route('rentals.create'))->assertForbidden();
    $this->actingAs($viewer)->post(route('rentals.store'), rentalPayload($this->hall))->assertForbidden();
    $this->actingAs($viewer)->get(route('rentals.edit', $rental))->assertForbidden();
    $this->actingAs($viewer)->put(route('rentals.update', $rental), rentalPayload($this->hall))->assertForbidden();
    $this->actingAs($viewer)->delete(route('rentals.destroy', $rental))->assertForbidden();
    $this->actingAs($viewer)->post(route('rentals.handover', $rental))->assertForbidden();
    $this->actingAs($viewer)->post(route('rentals.return', $rental))->assertForbidden();

    expect(Rental::count())->toBe(1)
        ->and(Protocol::count())->toBe(0);
});

test('the rental list needs the view permission', function () {
    $this->get(route('rentals.index'))->assertRedirect(route('login'));
    $this->actingAs(userWithPermissions([]))->get(route('rentals.index'))->assertForbidden();
});
