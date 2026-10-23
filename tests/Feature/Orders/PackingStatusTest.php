<?php

use App\Enums\OrderStatus;

dataset('non-packing statuses', [
    'new' => OrderStatus::NEW,
    'packed' => OrderStatus::PACKED,
    'in delivery' => OrderStatus::IN_DELIVERY,
    'delivered' => OrderStatus::DELIVERED,
]);

test('items of an order in progress can be packed', function () {
    $user = userWithPermissions(['orders.pack']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::IN_PROGRESS]);
    $item = $order->items()->first();

    $this->actingAs($user)
        ->post(route('packing.pack-item', $item))
        ->assertSessionHasNoErrors();

    expect($item->refresh()->quantity_packed)->toBe(2)
        ->and($item->is_packed)->toBeTrue();
});

test('items cannot be packed unless the order is in progress', function (OrderStatus $status) {
    $user = userWithPermissions(['orders.pack']);
    $order = makeOrder(makeContact(), $user, ['status' => $status]);
    $item = $order->items()->first();

    $this->actingAs($user)
        ->post(route('packing.pack-item', $item))
        ->assertSessionHasErrors();

    expect($item->refresh()->quantity_packed)->toBe(0)
        ->and($item->is_packed)->toBeFalse();
})->with('non-packing statuses');

test('packed items stay packed when the order is no longer in progress', function (OrderStatus $status) {
    $user = userWithPermissions(['orders.pack']);
    $order = makeOrder(makeContact(), $user, ['status' => $status]);
    $item = $order->items()->first();
    $item->update(['quantity_packed' => 2, 'is_packed' => true]);

    $this->actingAs($user)
        ->delete(route('packing.unpack-item', $item))
        ->assertSessionHasErrors();

    expect($item->refresh()->quantity_packed)->toBe(2)
        ->and($item->is_packed)->toBeTrue();
})->with('non-packing statuses');

test('an order in progress can be completed when all items are packed', function () {
    $user = userWithPermissions(['orders.pack']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::IN_PROGRESS]);
    $order->items()->update(['quantity_packed' => 2, 'is_packed' => true]);

    $this->actingAs($user)
        ->post(route('packing.complete', $order))
        ->assertRedirect(route('orders.show', $order));

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::PACKED)
        ->and($order->packed_by)->toBe($user->id);
});

test('completing the packing does not change an order that is not in progress', function (OrderStatus $status) {
    $user = userWithPermissions(['orders.pack']);
    $order = makeOrder(makeContact(), $user, ['status' => $status]);
    $order->items()->update(['quantity_packed' => 2, 'is_packed' => true]);

    $this->actingAs($user)
        ->post(route('packing.complete', $order))
        ->assertSessionHasErrors();

    expect($order->refresh()->status)->toBe($status);
})->with('non-packing statuses');

test('the error message for a wrong order status is translated', function () {
    $user = userWithPermissions(['orders.pack']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::DELIVERED]);
    $item = $order->items()->first();

    $this->actingAs($user)
        ->post(route('packing.pack-item', $item))
        ->assertSessionHasErrors();

    expect(session('errors')->first())->toBe(__('packing.messages.order_not_packable'));
});
