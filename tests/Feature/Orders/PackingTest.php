<?php

use App\Enums\OrderStatus;
use App\Models\User;

test('packing overview lists orders in progress with the contact name', function () {
    $user = userWithPermissions(['orders.pack']);
    $order = makeOrder(makeContact(['name' => 'Packkunde GmbH']), $user);

    $this->actingAs($user)
        ->get(route('packing.index'))
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('Packkunde GmbH');
});

test('packing overview hides orders that are not in progress', function () {
    $user = userWithPermissions(['orders.pack']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::NEW]);

    $this->actingAs($user)
        ->get(route('packing.index'))
        ->assertOk()
        ->assertDontSee($order->order_number);
});

test('packing overview is not accessible without permission', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('packing.index'))
        ->assertForbidden();
});
