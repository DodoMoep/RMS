<?php

use App\Enums\OrderStatus;
use App\Models\Order\Order;
use App\Models\Order\OrderHistory;

test('an open order can be deleted and the deletion is kept in the history', function () {
    $user = userWithPermissions(['orders.view', 'orders.delete']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::NEW]);

    $this->actingAs($user)
        ->delete(route('orders.destroy', $order))
        ->assertRedirect(route('orders.index'))
        ->assertSessionHas('ok');

    $this->assertSoftDeleted($order);
    expect(OrderHistory::where('order_id', $order->id)->where('event_type', 'order_deleted')->exists())->toBeTrue()
        ->and(OrderHistory::where('order_id', $order->id)->where('event_type', 'order_created')->exists())->toBeTrue();
});

test('a deleted order disappears from the order list and cannot be opened', function () {
    $user = userWithPermissions(['orders.view', 'orders.delete']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::NEW]);

    $this->actingAs($user)->delete(route('orders.destroy', $order));

    $this->actingAs($user)->get(route('orders.index'))->assertOk()->assertDontSee($order->order_number);
    $this->actingAs($user)->get(route('orders.show', $order->id))->assertNotFound();
});

test('a packed order cannot be deleted', function () {
    $user = userWithPermissions(['orders.view', 'orders.delete']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::PACKED]);

    $this->actingAs($user)
        ->delete(route('orders.destroy', $order))
        ->assertSessionHasErrors();

    $this->assertNotSoftDeleted($order);
});

test('deleting an order requires the delete permission', function () {
    $user = userWithPermissions(['orders.view']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::NEW]);

    $this->actingAs($user)->delete(route('orders.destroy', $order))->assertForbidden();

    $this->assertNotSoftDeleted($order);
});

test('items of a deleted order cannot be packed', function () {
    $user = userWithPermissions(['orders.view', 'orders.delete', 'orders.pack']);
    $order = makeOrder(makeContact(), $user);
    $item = $order->items()->first();

    $this->actingAs($user)->delete(route('orders.destroy', $order));

    $this->actingAs($user)->post(route('packing.pack-item', $item))->assertNotFound();
    expect($item->refresh()->quantity_packed)->toBe(0);
});

test('deleted orders are excluded from the analytics', function () {
    $user = userWithPermissions(['orders.view', 'orders.delete']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::NEW]);
    $this->actingAs($user)->delete(route('orders.destroy', $order));

    expect(Order::topItems(10, 'all'))->toHaveCount(0)
        ->and(Order::count())->toBe(0);
});
