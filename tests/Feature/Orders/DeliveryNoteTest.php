<?php

use App\Enums\OrderStatus;
use App\Models\Order\OrderHistory;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

test('delivery note template renders the contact data', function () {
    $user = User::factory()->create();
    $order = makeOrder(makeContact([
        'name' => 'Lieferkunde GmbH',
        'street' => 'Lieferweg 7',
        'zip_code' => '54321',
        'city' => 'Lieferstadt',
        'phone' => '0123 456789',
    ]), $user);

    $html = view('order.delivery-notes.template', ['order' => $order->load('contact', 'items')])->render();

    expect($html)
        ->toContain('Lieferkunde GmbH')
        ->toContain('Lieferweg 7')
        ->toContain('54321 Lieferstadt')
        ->toContain('0123 456789');
});

test('delivery note can be generated for a packed order', function () {
    Storage::fake('public');
    $user = userWithPermissions(['orders.print']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::PACKED]);

    $this->actingAs($user)
        ->post(route('delivery-notes.generate', $order))
        ->assertSessionHas('ok');

    $order->refresh();

    expect($order->delivery_note_path)->not->toBeNull();
    Storage::disk('public')->assertExists($order->delivery_note_path);
});

test('delivery note is not generated for an order that is not packed', function () {
    Storage::fake('public');
    $user = userWithPermissions(['orders.print']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::IN_PROGRESS]);

    $this->actingAs($user)
        ->post(route('delivery-notes.generate', $order))
        ->assertSessionHasErrors();

    expect($order->refresh()->delivery_note_path)->toBeNull();
});

test('printing the delivery note marks the order as in delivery', function () {
    Storage::fake('public');
    $user = userWithPermissions(['orders.print']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::PACKED]);

    $this->actingAs($user)
        ->post(route('delivery-notes.print', $order))
        ->assertOk();

    expect($order->refresh()->status)->toBe(OrderStatus::IN_DELIVERY)
        ->and($order->delivery_note_printed_at)->not->toBeNull();
});

test('the delivery note cannot be printed before the order is packed', function (OrderStatus $status) {
    Storage::fake('public');
    $user = userWithPermissions(['orders.print']);
    $order = makeOrder(makeContact(), $user, ['status' => $status]);

    $this->actingAs($user)
        ->post(route('delivery-notes.print', $order))
        ->assertSessionHasErrors();

    expect(session('errors')->first())->toBe(__('delivery_notes.messages.not_printable'));

    $order->refresh();

    expect($order->status)->toBe($status)
        ->and($order->delivery_note_path)->toBeNull()
        ->and($order->delivery_note_printed_at)->toBeNull();
})->with([
    'new' => OrderStatus::NEW,
    'in progress' => OrderStatus::IN_PROGRESS,
]);

test('printing the delivery note again does not change a shipped order', function (OrderStatus $status) {
    Storage::fake('public');
    $user = userWithPermissions(['orders.print']);
    $order = makeOrder(makeContact(), $user, ['status' => $status]);
    $order->generateDeliveryNote();
    $printedAt = now()->subDay()->startOfSecond();
    $order->update(['delivery_note_printed_at' => $printedAt]);
    $events = OrderHistory::where('order_id', $order->id)->where('event_type', 'delivery_note_printed')->count();

    $this->actingAs($user)
        ->post(route('delivery-notes.print', $order))
        ->assertOk();

    $order->refresh();

    expect($order->status)->toBe($status)
        ->and($order->delivery_note_printed_at->equalTo($printedAt))->toBeTrue()
        ->and(OrderHistory::where('order_id', $order->id)->where('event_type', 'delivery_note_printed')->count())->toBe($events);
})->with([
    'in delivery' => OrderStatus::IN_DELIVERY,
    'delivered' => OrderStatus::DELIVERED,
]);

test('delivery note routes are not accessible without permission', function () {
    $order = makeOrder(makeContact(), User::factory()->create(), ['status' => OrderStatus::PACKED]);

    $this->actingAs(User::factory()->create())
        ->post(route('delivery-notes.generate', $order))
        ->assertForbidden();
});
