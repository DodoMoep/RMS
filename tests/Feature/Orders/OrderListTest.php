<?php

use App\Enums\OrderStatus;
use App\Models\Order\Order;

/**
 * Auftragsliste, Detailseite und Historie (OrderController::index, show, history).
 */
function listedNumbers($response): array
{
    return $response->viewData('orders')->pluck('order_number')->sort()->values()->all();
}

beforeEach(function () {
    $this->user = userWithPermissions(['orders.view']);
    $this->contactA = makeContact(['name' => 'Alpha Veranstaltungen']);
    $this->contactB = makeContact(['name' => 'Beta Catering']);
});

// ------------------------------------------------------------------- Liste

test('the order list shows every order with customer and status', function () {
    $order = makeOrder($this->contactA, $this->user, ['status' => OrderStatus::PACKED]);

    $this->actingAs($this->user)
        ->get(route('orders.index'))
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('Alpha Veranstaltungen')
        ->assertSee(OrderStatus::PACKED->label());
});

test('the order list can be filtered by status', function () {
    $new = makeOrder($this->contactA, $this->user, ['status' => OrderStatus::NEW]);
    makeOrder($this->contactA, $this->user, ['status' => OrderStatus::PACKED]);

    $response = $this->actingAs($this->user)->get(route('orders.index', ['status' => 'new']))->assertOk();

    expect(listedNumbers($response))->toBe([$new->order_number]);
});

test('the order list can be searched by order number', function () {
    $first = makeOrder($this->contactA, $this->user);
    makeOrder($this->contactB, $this->user);

    $response = $this->actingAs($this->user)->get(route('orders.index', ['search' => $first->order_number]));

    expect(listedNumbers($response))->toBe([$first->order_number]);
});

test('the order list can be searched by customer name', function () {
    $alpha = makeOrder($this->contactA, $this->user);
    makeOrder($this->contactB, $this->user);

    $response = $this->actingAs($this->user)->get(route('orders.index', ['search' => 'Alpha']));

    expect(listedNumbers($response))->toBe([$alpha->order_number]);
});

test('a search without a match shows an empty list', function () {
    makeOrder($this->contactA, $this->user);

    $response = $this->actingAs($this->user)->get(route('orders.index', ['search' => 'gibt-es-nicht']))->assertOk();

    expect($response->viewData('orders'))->toHaveCount(0);
});

test('the order list can be limited to a creation period', function () {
    $old = makeOrder($this->contactA, $this->user);
    $old->forceFill(['created_at' => now()->subDays(20)])->saveQuietly();
    $recent = makeOrder($this->contactA, $this->user);
    $recent->forceFill(['created_at' => now()->subDays(2)])->saveQuietly();
    $newest = makeOrder($this->contactA, $this->user);

    $from = $this->actingAs($this->user)->get(route('orders.index', ['date_from' => now()->subDays(5)->toDateString()]));
    $to = $this->actingAs($this->user)->get(route('orders.index', ['date_to' => now()->subDays(10)->toDateString()]));
    $both = $this->actingAs($this->user)->get(route('orders.index', [
        'date_from' => now()->subDays(5)->toDateString(),
        'date_to' => now()->subDay()->toDateString(),
    ]));

    expect(listedNumbers($from))->toBe(collect([$recent, $newest])->pluck('order_number')->sort()->values()->all())
        ->and(listedNumbers($to))->toBe([$old->order_number])
        ->and(listedNumbers($both))->toBe([$recent->order_number]);
});

test('filters can be combined', function () {
    $match = makeOrder($this->contactA, $this->user, ['status' => OrderStatus::NEW]);
    makeOrder($this->contactA, $this->user, ['status' => OrderStatus::PACKED]);
    makeOrder($this->contactB, $this->user, ['status' => OrderStatus::NEW]);

    $response = $this->actingAs($this->user)->get(route('orders.index', ['status' => 'new', 'search' => 'Alpha']));

    expect(listedNumbers($response))->toBe([$match->order_number]);
});

test('the order list is paginated with 15 orders per page', function () {
    foreach (range(1, 16) as $i) {
        makeOrder($this->contactA, $this->user);
    }

    $first = $this->actingAs($this->user)->get(route('orders.index'));
    $second = $this->actingAs($this->user)->get(route('orders.index', ['page' => 2]));

    expect($first->viewData('orders'))->toHaveCount(15)
        ->and($first->viewData('orders')->total())->toBe(16)
        ->and($second->viewData('orders'))->toHaveCount(1);
});

test('the newest order comes first', function () {
    $old = makeOrder($this->contactA, $this->user);
    $old->forceFill(['created_at' => now()->subDays(3)])->saveQuietly();
    $new = makeOrder($this->contactA, $this->user);

    $response = $this->actingAs($this->user)->get(route('orders.index'));

    expect($response->viewData('orders')->first()->id)->toBe($new->id);
});

test('deleted orders are not listed', function () {
    $kept = makeOrder($this->contactA, $this->user);
    $deleted = makeOrder($this->contactA, $this->user);
    $deleted->delete();

    $response = $this->actingAs($this->user)->get(route('orders.index'));

    expect(listedNumbers($response))->toBe([$kept->order_number]);
});

test('the order list needs the view permission', function () {
    // Zuerst als Gast, weil actingAs die Anmeldung fuer den Rest des Tests behaelt
    $this->get(route('orders.index'))->assertRedirect(route('login'));
    $this->actingAs(userWithPermissions([]))->get(route('orders.index'))->assertForbidden();
});

// ------------------------------------------------------------------ Details

test('the order page shows customer, items and creator', function () {
    $order = makeOrder($this->contactA, $this->user, ['status' => OrderStatus::IN_PROGRESS, 'notes' => 'Bitte klingeln']);
    $order->items()->create(['article_name' => 'Zusatzartikel', 'quantity_ordered' => 6]);

    $this->actingAs($this->user)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('Alpha Veranstaltungen')
        ->assertSee('Testartikel')
        ->assertSee('Zusatzartikel')
        ->assertSee('Bitte klingeln')
        ->assertSee($this->user->name);
});

test('the order page shows the status button only with the status permission', function () {
    $order = makeOrder($this->contactA, $this->user, ['status' => OrderStatus::NEW]);

    $this->actingAs($this->user)->get(route('orders.show', $order))
        ->assertDontSee(route('orders.update-status', $order));

    $manager = userWithPermissions(['orders.view', 'orders.update-status']);
    $this->actingAs($manager)->get(route('orders.show', $order))
        ->assertSee(route('orders.update-status', $order));
});

test('the order page of a deleted order is not found', function () {
    $order = makeOrder($this->contactA, $this->user);
    $order->delete();

    $this->actingAs($this->user)->get(route('orders.show', $order->id))->assertNotFound();
});

test('the order page of an unknown order is not found', function () {
    $this->actingAs($this->user)
        ->get(route('orders.show', '00000000-0000-0000-0000-000000000000'))
        ->assertNotFound();
});

// ---------------------------------------------------------------- Historie

test('the history lists the events of an order', function () {
    $viewer = userWithPermissions(['orders.view', 'orders.view-history']);
    $order = makeOrder($this->contactA, $this->user, ['status' => OrderStatus::NEW]);
    $order->update(['status' => OrderStatus::IN_PROGRESS]);

    $response = $this->actingAs($viewer)->get(route('orders.history', $order))->assertOk();

    expect($response->viewData('order')->history->pluck('event_type')->sort()->values()->all())
        ->toBe(['order_created', 'status_changed'])
        ->and($response->viewData('order')->is($order))->toBeTrue();
});

test('the history needs its own permission', function () {
    $order = makeOrder($this->contactA, $this->user);

    $this->actingAs($this->user)->get(route('orders.history', $order))->assertForbidden();
});

test('the history of another order is not mixed in', function () {
    $viewer = userWithPermissions(['orders.view', 'orders.view-history']);
    $order = makeOrder($this->contactA, $this->user);
    $other = makeOrder($this->contactB, $this->user);
    $other->update(['status' => OrderStatus::IN_PROGRESS]);

    $response = $this->actingAs($viewer)->get(route('orders.history', $order));

    expect($response->viewData('order')->history)->toHaveCount(1)
        ->and(Order::count())->toBe(2);
});
