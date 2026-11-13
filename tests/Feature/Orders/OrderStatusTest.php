<?php

use App\Enums\OrderStatus;
use App\Models\Order\Order;

/**
 * Statuswechsel von Hand (OrderController::updateStatus).
 */
/**
 * Die erlaubten Schritte stehen hier fest und werden bewusst nicht aus canTransitionTo()
 * abgeleitet, sonst wuerde der Test mit einer geaenderten Regel mitwandern.
 * Von "delivered" aus ist jeder Schritt gesperrt und hat einen eigenen Test.
 *
 * @return array<string, array{0: OrderStatus, 1: OrderStatus}>
 */
function statusPairs(bool $allowed): array
{
    $allowedSteps = [
        'new to in_progress' => [OrderStatus::NEW, OrderStatus::IN_PROGRESS],
        'in_progress to packed' => [OrderStatus::IN_PROGRESS, OrderStatus::PACKED],
        'packed to in_delivery' => [OrderStatus::PACKED, OrderStatus::IN_DELIVERY],
        'in_delivery to delivered' => [OrderStatus::IN_DELIVERY, OrderStatus::DELIVERED],
    ];

    if ($allowed) {
        return $allowedSteps;
    }

    $forbidden = [];

    foreach (OrderStatus::cases() as $from) {
        foreach (OrderStatus::cases() as $to) {
            $key = "{$from->value} to {$to->value}";

            if ($from !== OrderStatus::DELIVERED && ! isset($allowedSteps[$key])) {
                $forbidden[$key] = [$from, $to];
            }
        }
    }

    return $forbidden;
}

function packAllItems(Order $order): void
{
    $order->items()->update(['quantity_packed' => 2, 'is_packed' => true]);
}

beforeEach(function () {
    $this->user = userWithPermissions(['orders.view', 'orders.update-status']);
    $this->order = makeOrder(makeContact(), $this->user, ['status' => OrderStatus::NEW]);
});

// -------------------------------------------------------- Erlaubte Wechsel

test('the allowed status chain can be walked from new to delivered', function () {
    $this->actingAs($this->user);

    foreach ([OrderStatus::IN_PROGRESS, OrderStatus::PACKED, OrderStatus::IN_DELIVERY, OrderStatus::DELIVERED] as $next) {
        if ($next === OrderStatus::PACKED) {
            packAllItems($this->order);
        }

        $this->patch(route('orders.update-status', $this->order), ['status' => $next->value])
            ->assertSessionHas('ok', __('orders.messages.status_updated'));

        expect($this->order->refresh()->status)->toBe($next);
    }
});

test('every allowed single step is accepted', function (OrderStatus $from, OrderStatus $to) {
    $this->order->update(['status' => $from]);
    packAllItems($this->order);

    $this->actingAs($this->user)
        ->patch(route('orders.update-status', $this->order), ['status' => $to->value])
        ->assertSessionHas('ok');

    expect($this->order->refresh()->status)->toBe($to);
})->with(fn () => statusPairs(true));

test('every status change is written to the history', function () {
    $this->actingAs($this->user)
        ->patch(route('orders.update-status', $this->order), ['status' => 'in_progress']);

    $event = $this->order->history()->where('event_type', 'status_changed')->firstOrFail();

    expect($event->user_id)->toBe($this->user->id)
        ->and($event->metadata)->toBe(['old_status' => 'new', 'new_status' => 'in_progress']);
});

test('delivering an order sets the delivery time', function () {
    $this->order->update(['status' => OrderStatus::IN_DELIVERY]);
    expect($this->order->delivered_at)->toBeNull();

    $this->actingAs($this->user)
        ->patch(route('orders.update-status', $this->order), ['status' => 'delivered']);

    expect($this->order->refresh()->delivered_at)->not->toBeNull()
        ->and($this->order->delivered_at->diffInSeconds(now(), true))->toBeLessThan(60);
});

test('other status changes do not set the delivery time', function () {
    $this->actingAs($this->user)
        ->patch(route('orders.update-status', $this->order), ['status' => 'in_progress']);

    expect($this->order->refresh()->delivered_at)->toBeNull();
});

// ------------------------------------------------------ Unzulaessige Wechsel

test('a step that is not allowed is rejected and changes nothing', function (OrderStatus $from, OrderStatus $to) {
    $this->order->update(['status' => $from]);
    packAllItems($this->order);
    // Das Setzen des Ausgangsstatus schreibt selbst einen Eintrag, daher vorher zaehlen
    $eventsBefore = $this->order->history()->where('event_type', 'status_changed')->count();

    $this->actingAs($this->user)
        ->patch(route('orders.update-status', $this->order), ['status' => $to->value])
        ->assertSessionHas('error', __('orders.messages.invalid_status_transition'));

    expect($this->order->refresh()->status)->toBe($from)
        ->and($this->order->history()->where('event_type', 'status_changed')->count())->toBe($eventsBefore);
})->with(fn () => statusPairs(false));

test('a delivered order cannot be changed any more', function (OrderStatus $target) {
    $this->order->update(['status' => OrderStatus::DELIVERED]);

    $this->actingAs($this->user)
        ->patch(route('orders.update-status', $this->order), ['status' => $target->value])
        ->assertSessionHas('error', __('orders.messages.cannot_edit_delivered'));

    expect($this->order->refresh()->status)->toBe(OrderStatus::DELIVERED);
})->with(OrderStatus::cases());

test('an order can only be marked as packed when every item is packed', function () {
    $this->order->update(['status' => OrderStatus::IN_PROGRESS]);

    $this->actingAs($this->user)
        ->patch(route('orders.update-status', $this->order), ['status' => 'packed'])
        ->assertSessionHas('error', __('orders.messages.all_items_must_be_packed'));

    expect($this->order->refresh()->status)->toBe(OrderStatus::IN_PROGRESS);
});

test('a partially packed item still blocks the packed status', function () {
    $this->order->update(['status' => OrderStatus::IN_PROGRESS]);
    $this->order->items()->update(['quantity_packed' => 1]);

    $this->actingAs($this->user)
        ->patch(route('orders.update-status', $this->order), ['status' => 'packed'])
        ->assertSessionHas('error', __('orders.messages.all_items_must_be_packed'));

    expect($this->order->refresh()->status)->toBe(OrderStatus::IN_PROGRESS);
});

// -------------------------------------------------------------- Validierung

test('an unknown or missing status value is rejected', function (?string $value) {
    $this->actingAs($this->user)
        ->patch(route('orders.update-status', $this->order), ['status' => $value])
        ->assertSessionHasErrors('status');

    expect($this->order->refresh()->status)->toBe(OrderStatus::NEW);
})->with(['unknown' => ['fliegend'], 'empty' => [''], 'null' => [null]]);

test('the status can only be changed with the permission', function () {
    $viewer = userWithPermissions(['orders.view']);

    $this->actingAs($viewer)
        ->patch(route('orders.update-status', $this->order), ['status' => 'in_progress'])
        ->assertForbidden();

    expect($this->order->refresh()->status)->toBe(OrderStatus::NEW);
});

test('a deleted order cannot change its status', function () {
    $this->order->delete();

    $this->actingAs($this->user)
        ->patch(route('orders.update-status', $this->order->id), ['status' => 'in_progress'])
        ->assertNotFound();
});
