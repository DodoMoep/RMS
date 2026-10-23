<?php

use App\Models\Rental\Hall;
use App\Models\Rental\InventoryItem;
use App\Models\User;

test('edit form shows the existing inventory item', function () {
    $user = userWithPermissions(['inventory.view', 'inventory.edit']);
    $item = InventoryItem::create(['name' => 'Bierzeltgarnitur', 'sku' => 'BZ-1']);

    $this->actingAs($user)
        ->get(route('inventory-items.edit', $item))
        ->assertOk()
        ->assertSee('Bierzeltgarnitur')
        ->assertSee('BZ-1');
});

test('inventory item can be updated', function () {
    $user = userWithPermissions(['inventory.view', 'inventory.edit']);
    $item = InventoryItem::create(['name' => 'Alter Name']);

    $this->actingAs($user)
        ->put(route('inventory-items.update', $item), ['name' => 'Neuer Name', 'sku' => 'NN-1'])
        ->assertRedirect(route('inventory-items.index'));

    expect($item->refresh()->name)->toBe('Neuer Name')
        ->and($item->sku)->toBe('NN-1');
    expect(InventoryItem::count())->toBe(1);
});

test('inventory item can be deleted', function () {
    $user = userWithPermissions(['inventory.view', 'inventory.delete']);
    $item = InventoryItem::create(['name' => 'Wegwerf-Position']);

    $this->actingAs($user)
        ->delete(route('inventory-items.destroy', $item))
        ->assertSessionHas('ok');

    $this->assertModelMissing($item);
});

test('inventory item assigned to a hall cannot be deleted', function () {
    $user = userWithPermissions(['inventory.view', 'inventory.delete']);
    $item = InventoryItem::create(['name' => 'Zugeordnete Position']);
    $hall = Hall::create(['name' => 'Testhalle']);
    $hall->inventory()->attach($item->id, ['quantity' => 4]);

    $this->actingAs($user)
        ->delete(route('inventory-items.destroy', $item))
        ->assertSessionHasErrors();

    $this->assertModelExists($item);
});

test('inventory item routes are not accessible without permission', function () {
    $item = InventoryItem::create(['name' => 'Geschützt']);

    $this->actingAs(User::factory()->create())
        ->get(route('inventory-items.edit', $item))
        ->assertForbidden();
});
