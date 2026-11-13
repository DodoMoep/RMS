<?php

use App\Enums\OrderStatus;
use App\Models\Order\Order;
use App\Models\Order\OrderHistory;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

test('deleting a user keeps the orders the user created', function () {
    $admin = userWithPermissions(['user.delete']);
    $creator = User::factory()->create();
    $order = makeOrder(makeContact(), $creator);
    $historyCount = OrderHistory::where('order_id', $order->id)->count();

    $this->actingAs($admin)
        ->delete(route('users.destroy', $creator))
        ->assertRedirect();

    $this->assertModelMissing($creator);
    $this->assertModelExists($order);

    expect($order->refresh()->created_by)->toBeNull()
        ->and($order->items()->count())->toBe(1)
        ->and(OrderHistory::where('order_id', $order->id)->count())->toBe($historyCount);
});

test('deleting a user keeps the packing data of the orders', function () {
    $admin = userWithPermissions(['user.delete']);
    $creator = User::factory()->create();
    $packer = User::factory()->create();
    $order = makeOrder(makeContact(), $creator, ['status' => OrderStatus::PACKED, 'packed_by' => $packer->id]);
    $item = $order->items()->first();
    $item->update(['quantity_packed' => 2, 'is_packed' => true, 'packed_by' => $packer->id]);

    $this->actingAs($admin)->delete(route('users.destroy', $packer))->assertRedirect();

    expect($order->refresh()->packed_by)->toBeNull()
        ->and($order->status)->toBe(OrderStatus::PACKED)
        ->and($item->refresh()->packed_by)->toBeNull()
        ->and($item->is_packed)->toBeTrue();
});

test('orders without a creator can still be listed and opened', function () {
    $admin = userWithPermissions(['user.delete']);
    $creator = User::factory()->create();
    $order = makeOrder(makeContact(), $creator);
    $this->actingAs($admin)->delete(route('users.destroy', $creator));

    $viewer = userWithPermissions(['orders.view', 'orders.view-history']);

    $this->actingAs($viewer)->get(route('orders.index'))->assertOk()->assertSee($order->order_number);
    $this->actingAs($viewer)->get(route('orders.show', $order))->assertOk()->assertSee($order->order_number);
    $this->actingAs($viewer)->get(route('orders.history', $order))->assertOk();
});

test('orders without a creator can still be packed', function () {
    $admin = userWithPermissions(['user.delete']);
    $creator = User::factory()->create();
    $order = makeOrder(makeContact(), $creator, ['status' => OrderStatus::IN_PROGRESS]);
    $this->actingAs($admin)->delete(route('users.destroy', $creator));

    $packer = userWithPermissions(['orders.pack']);

    $this->actingAs($packer)->get(route('packing.index'))->assertOk()->assertSee($order->order_number);
    $this->actingAs($packer)->get(route('packing.show', $order))->assertOk();
});

test('the deletion of a user is written to the activity log', function () {
    $admin = userWithPermissions(['user.delete']);
    $user = User::factory()->create();

    $this->actingAs($admin)->delete(route('users.destroy', $user));

    expect(Activity::where('log_name', 'admin')->where('event', 'user.deleted')->exists())->toBeTrue();
});

test('a user cannot delete their own account in the user administration', function () {
    $admin = userWithPermissions(['user.delete']);

    $this->actingAs($admin)->delete(route('users.destroy', $admin))->assertForbidden();

    $this->assertModelExists($admin);
});

test('deleting a user requires the delete permission', function () {
    $user = User::factory()->create();
    $order = makeOrder(makeContact(), $user);

    $this->actingAs(userWithPermissions(['user.list']))
        ->delete(route('users.destroy', $user))
        ->assertForbidden();

    $this->assertModelExists($user);
    expect(Order::find($order->id)->created_by)->toBe($user->id);
});
