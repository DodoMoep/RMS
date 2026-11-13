<?php

use App\Enums\ContactType;
use App\Models\Rental\Hall;
use App\Models\Rental\InventoryItem;
use App\Models\Rental\Rental;
use Illuminate\Support\Facades\DB;

/**
 * Hallenverwaltung (HallController), einschliesslich der Inventarzuordnung.
 */
function hallPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Festhalle Nord',
        'address' => 'Hallenweg 3, 12345 Testhausen',
        'notes' => 'Bitte Schluessel abholen',
    ], $overrides);
}

function hallRental(Hall $hall): Rental
{
    return Rental::create([
        'contact_id' => makeContact(['type' => ContactType::Tenant])->id,
        'hall_id' => $hall->id,
        'start' => now()->addDay(),
        'end' => now()->addDays(2),
        'status' => 'scheduled',
    ]);
}

beforeEach(function () {
    $this->user = userWithPermissions(['halls.view', 'halls.create', 'halls.edit', 'halls.delete']);
    $this->tables = InventoryItem::create(['name' => 'Tisch', 'sku' => 'T-1']);
    $this->chairs = InventoryItem::create(['name' => 'Stuhl', 'sku' => 'S-1']);
});

// ---------------------------------------------------------------- Anlegen

test('a hall can be created', function () {
    $this->actingAs($this->user)
        ->post(route('halls.store'), hallPayload())
        ->assertRedirect(route('halls.index'))
        ->assertSessionHas('ok', __('halls.messages.created'));

    $hall = Hall::firstOrFail();

    expect($hall->name)->toBe('Festhalle Nord')
        ->and($hall->address)->toBe('Hallenweg 3, 12345 Testhausen')
        ->and($hall->notes)->toBe('Bitte Schluessel abholen')
        ->and($hall->inventory)->toHaveCount(0);
});

test('a hall can be created with inventory and quantities', function () {
    $this->actingAs($this->user)->post(route('halls.store'), hallPayload([
        'inventory' => [
            $this->tables->id => ['selected' => '1', 'quantity' => '12'],
            $this->chairs->id => ['selected' => '1', 'quantity' => '80'],
        ],
    ]))->assertSessionHasNoErrors();

    $inventory = Hall::firstOrFail()->inventory->pluck('pivot.quantity', 'name')->all();

    expect($inventory)->toBe(['Tisch' => 12, 'Stuhl' => 80]);
});

test('inventory that is not selected or has no quantity is not assigned', function () {
    $this->actingAs($this->user)->post(route('halls.store'), hallPayload([
        'inventory' => [
            $this->tables->id => ['quantity' => '12'],
            $this->chairs->id => ['selected' => '1', 'quantity' => ''],
        ],
    ]))->assertSessionHasNoErrors();

    expect(Hall::firstOrFail()->inventory)->toHaveCount(0);
});

test('invalid hall data is rejected', function (array $overrides, string $errorField) {
    $this->actingAs($this->user)
        ->post(route('halls.store'), hallPayload($overrides))
        ->assertSessionHasErrors($errorField);

    expect(Hall::count())->toBe(0);
})->with([
    'no name' => [['name' => ''], 'name'],
    'name too short' => [['name' => 'A'], 'name'],
    'address too long' => [['address' => str_repeat('a', 256)], 'address'],
    'quantity zero' => [['inventory' => ['x' => ['selected' => '1', 'quantity' => '0']]], 'inventory.x.quantity'],
    'quantity not a number' => [['inventory' => ['x' => ['selected' => '1', 'quantity' => 'viele']]], 'inventory.x.quantity'],
]);

test('address and notes are optional', function () {
    $this->actingAs($this->user)
        ->post(route('halls.store'), ['name' => 'Nur Name'])
        ->assertSessionHasNoErrors();

    expect(Hall::firstOrFail()->address)->toBeNull();
});

// -------------------------------------------------------------- Bearbeiten

test('a hall can be changed', function () {
    $hall = Hall::create(['name' => 'Alte Halle']);

    $this->actingAs($this->user)
        ->put(route('halls.update', $hall), hallPayload(['name' => 'Neue Halle']))
        ->assertRedirect(route('halls.index'))
        ->assertSessionHas('ok', __('halls.messages.updated'));

    expect($hall->refresh()->name)->toBe('Neue Halle')
        ->and($hall->notes)->toBe('Bitte Schluessel abholen');
});

test('changing the inventory replaces the assignment', function () {
    $hall = Hall::create(['name' => 'Festhalle']);
    $hall->inventory()->sync([
        $this->tables->id => ['quantity' => 5],
        $this->chairs->id => ['quantity' => 40],
    ]);

    $this->actingAs($this->user)->put(route('halls.update', $hall), hallPayload([
        'inventory' => [
            $this->tables->id => ['selected' => '1', 'quantity' => '9'],
        ],
    ]))->assertSessionHasNoErrors();

    expect($hall->refresh()->inventory->pluck('pivot.quantity', 'name')->all())->toBe(['Tisch' => 9]);
});

test('sending no inventory removes the whole assignment', function () {
    $hall = Hall::create(['name' => 'Festhalle']);
    $hall->inventory()->sync([$this->tables->id => ['quantity' => 5]]);

    $this->actingAs($this->user)->put(route('halls.update', $hall), hallPayload());

    expect($hall->refresh()->inventory)->toHaveCount(0)
        ->and(InventoryItem::count())->toBe(2);
});

test('invalid data is rejected when changing a hall', function () {
    $hall = Hall::create(['name' => 'Festhalle']);

    $this->actingAs($this->user)
        ->put(route('halls.update', $hall), hallPayload(['name' => 'X']))
        ->assertSessionHasErrors('name');

    expect($hall->refresh()->name)->toBe('Festhalle');
});

// ----------------------------------------------------------------- Loeschen

test('a hall without rentals is deleted together with its inventory assignment', function () {
    $hall = Hall::create(['name' => 'Wegwerfhalle']);
    $hall->inventory()->sync([$this->tables->id => ['quantity' => 5]]);

    $this->actingAs($this->user)
        ->delete(route('halls.destroy', $hall))
        ->assertSessionHas('ok', __('halls.messages.deleted'));

    expect(Hall::find($hall->id))->toBeNull()
        ->and(DB::table('hall_inventory')->count())->toBe(0)
        ->and(InventoryItem::count())->toBe(2);
});

test('a hall with rentals cannot be deleted', function () {
    $hall = Hall::create(['name' => 'Gebuchte Halle']);
    hallRental($hall);

    $this->actingAs($this->user)->delete(route('halls.destroy', $hall))->assertRedirect();

    expect(session('errors')->first())->toBe(__('halls.messages.has_rentals'))
        ->and(Hall::find($hall->id))->not->toBeNull();
});

// -------------------------------------------------------------- Seiten, Rechte

test('the hall list shows the halls', function () {
    Hall::create(['name' => 'Listenhalle Eins']);
    Hall::create(['name' => 'Listenhalle Zwei']);

    $this->actingAs($this->user)
        ->get(route('halls.index'))
        ->assertOk()
        ->assertSee('Listenhalle Eins')
        ->assertSee('Listenhalle Zwei');
});

test('the hall list is paginated with 15 halls per page', function () {
    foreach (range(1, 16) as $i) {
        Hall::create(['name' => "Halle {$i}"]);
    }

    $first = $this->actingAs($this->user)->get(route('halls.index'));
    $second = $this->actingAs($this->user)->get(route('halls.index', ['page' => 2]));

    expect($first->viewData('halls'))->toHaveCount(15)
        ->and($second->viewData('halls'))->toHaveCount(1);
});

test('the hall forms offer all inventory items and show the assignment', function () {
    $hall = Hall::create(['name' => 'Formularhalle']);
    $hall->inventory()->sync([$this->tables->id => ['quantity' => 7]]);

    $this->actingAs($this->user)->get(route('halls.create'))
        ->assertOk()->assertSee('Tisch')->assertSee('Stuhl');

    $this->actingAs($this->user)->get(route('halls.edit', $hall))
        ->assertOk()->assertSee('Formularhalle')->assertSee('Tisch')->assertSee('Stuhl');
});

test('every hall action needs its own permission', function () {
    $hall = Hall::create(['name' => 'Geschuetzte Halle']);
    $viewer = userWithPermissions(['halls.view']);

    $this->actingAs($viewer)->get(route('halls.index'))->assertOk();
    $this->actingAs($viewer)->get(route('halls.create'))->assertForbidden();
    $this->actingAs($viewer)->post(route('halls.store'), hallPayload())->assertForbidden();
    $this->actingAs($viewer)->get(route('halls.edit', $hall))->assertForbidden();
    $this->actingAs($viewer)->put(route('halls.update', $hall), hallPayload())->assertForbidden();
    $this->actingAs($viewer)->delete(route('halls.destroy', $hall))->assertForbidden();

    expect(Hall::count())->toBe(1)
        ->and($hall->refresh()->name)->toBe('Geschuetzte Halle');
});

test('the hall list needs the view permission', function () {
    $this->get(route('halls.index'))->assertRedirect(route('login'));
    $this->actingAs(userWithPermissions([]))->get(route('halls.index'))->assertForbidden();
});
