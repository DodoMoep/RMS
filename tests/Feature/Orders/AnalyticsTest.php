<?php

use App\Enums\OrderStatus;
use App\Models\Order\Article;
use App\Models\Order\Order;
use App\Models\User;

/**
 * Auswertungsseite und JSON-Endpunkte (OrderAnalyticsController).
 * Order::ordersOverTime nutzt die MariaDB-Funktion DATE_FORMAT, die hier nachgebildet wird.
 */
function analyticsOrder(User $creator, OrderStatus $status, int $daysAgo = 1, array $overrides = []): Order
{
    $order = makeOrder(makeContact(), $creator, array_merge(['status' => $status], $overrides));
    $order->forceFill(['created_at' => now()->subDays($daysAgo)])->saveQuietly();

    return $order;
}

/**
 * Auftrag mit genau einer Position (die Standardposition von makeOrder wird entfernt,
 * damit sie die Rangliste der Top-Artikel nicht verfaelscht).
 */
function analyticsOrderWithItem(User $creator, Article $article, int $quantity, int $daysAgo = 1): Order
{
    $order = analyticsOrder($creator, OrderStatus::NEW, $daysAgo);
    $order->items()->delete();
    $order->items()->create([
        'article_id' => $article->id, 'article_name' => $article->name, 'quantity_ordered' => $quantity,
    ]);

    return $order;
}

beforeEach(function () {
    registerSqliteDateFormat();
    $this->user = userWithPermissions(['analytics.view']);
});

// ------------------------------------------------------------------- Seite

test('the analytics page counts orders of the default period of 30 days', function () {
    analyticsOrder($this->user, OrderStatus::NEW, 1);
    analyticsOrder($this->user, OrderStatus::IN_PROGRESS, 10);
    analyticsOrder($this->user, OrderStatus::DELIVERED, 20);
    analyticsOrder($this->user, OrderStatus::NEW, 60);

    $response = $this->actingAs($this->user)->get(route('analytics.index'))->assertOk();

    expect($response->viewData('period'))->toBe(30)
        ->and($response->viewData('totalOrders'))->toBe(3)
        ->and($response->viewData('activeOrders'))->toBe(2);
});

test('the analytics page respects the selected period', function (string $period, int $total) {
    analyticsOrder($this->user, OrderStatus::NEW, 1);
    analyticsOrder($this->user, OrderStatus::NEW, 10);
    analyticsOrder($this->user, OrderStatus::NEW, 100);
    analyticsOrder($this->user, OrderStatus::NEW, 500);

    $response = $this->actingAs($this->user)->get(route('analytics.index', ['period' => $period]))->assertOk();

    expect($response->viewData('period'))->toBe($period)
        ->and($response->viewData('totalOrders'))->toBe($total);
})->with([
    '7 days' => ['7', 1],
    '14 days' => ['14', 2],
    '180 days' => ['180', 3],
    '365 days' => ['365', 3],
    'all time' => ['all', 4],
]);

test('active orders are only new and in progress ones', function () {
    foreach (OrderStatus::cases() as $status) {
        analyticsOrder($this->user, $status);
    }

    $response = $this->actingAs($this->user)->get(route('analytics.index', ['period' => 'all']));

    expect($response->viewData('totalOrders'))->toBe(5)
        ->and($response->viewData('activeOrders'))->toBe(2);
});

test('packed items are counted for packed, delivery and delivered orders only', function () {
    foreach ([OrderStatus::NEW, OrderStatus::IN_PROGRESS, OrderStatus::PACKED, OrderStatus::IN_DELIVERY, OrderStatus::DELIVERED] as $status) {
        analyticsOrder($this->user, $status);
    }

    $response = $this->actingAs($this->user)->get(route('analytics.index'));

    // Eine Position je Auftrag, drei Auftraege im gepackten Bereich
    expect($response->viewData('itemsPacked'))->toBe(3);
});

test('deleted orders do not count on the analytics page', function () {
    analyticsOrder($this->user, OrderStatus::NEW);
    analyticsOrder($this->user, OrderStatus::NEW)->delete();

    $response = $this->actingAs($this->user)->get(route('analytics.index'));

    expect($response->viewData('totalOrders'))->toBe(1);
});

test('the analytics page renders without any data', function () {
    $response = $this->actingAs($this->user)->get(route('analytics.index', ['period' => 'all']))->assertOk();

    expect($response->viewData('totalOrders'))->toBe(0)
        ->and($response->viewData('activeOrders'))->toBe(0)
        ->and($response->viewData('itemsPacked'))->toBe(0);
});

test('the analytics page rejects an invalid period', function (string $period) {
    $this->actingAs($this->user)
        ->get(route('analytics.index', ['period' => $period]))
        ->assertSessionHasErrors('period');
})->with(['text' => 'abc', 'too long' => '9999', 'zero' => '0']);

// -------------------------------------------------------------- Pro Status

test('orders by status groups the orders of the last 30 days', function () {
    analyticsOrder($this->user, OrderStatus::NEW, 1);
    analyticsOrder($this->user, OrderStatus::NEW, 2);
    analyticsOrder($this->user, OrderStatus::PACKED, 3);
    analyticsOrder($this->user, OrderStatus::NEW, 90);

    $response = $this->actingAs($this->user)->getJson(route('analytics.api.status'))->assertOk();

    expect(collect($response->json())->pluck('count', 'status')->all())->toBe(['new' => 2, 'packed' => 1]);
});

// ---------------------------------------------------------- Verlauf pro Zeit

test('orders over time groups by day by default', function () {
    analyticsOrder($this->user, OrderStatus::NEW, 1);
    analyticsOrder($this->user, OrderStatus::NEW, 1);
    analyticsOrder($this->user, OrderStatus::NEW, 3);

    $response = $this->actingAs($this->user)->getJson(route('analytics.api.timeline'))->assertOk();

    expect(collect($response->json())->pluck('count', 'period')->all())->toBe([
        now()->subDays(3)->format('Y-m-d') => 1,
        now()->subDays(1)->format('Y-m-d') => 2,
    ]);
});

test('orders over time can group by month', function () {
    analyticsOrder($this->user, OrderStatus::NEW, 1);
    analyticsOrder($this->user, OrderStatus::NEW, 2);

    $response = $this->actingAs($this->user)
        ->getJson(route('analytics.api.timeline', ['period' => 'month', 'days' => 365]))
        ->assertOk();

    $months = collect($response->json())->pluck('period')->all();

    expect(array_sum(collect($response->json())->pluck('count')->all()))->toBe(2)
        ->and($months)->each->toMatch('/^\d{4}-\d{2}$/');
});

test('orders over time can group by hour and by week', function (string $group, string $pattern) {
    analyticsOrder($this->user, OrderStatus::NEW, 1);

    $response = $this->actingAs($this->user)
        ->getJson(route('analytics.api.timeline', ['period' => $group]))
        ->assertOk();

    expect($response->json())->toHaveCount(1)
        ->and($response->json('0.period'))->toMatch($pattern)
        ->and($response->json('0.count'))->toBe(1);
})->with([
    'hour' => ['hour', '/^\d{4}-\d{2}-\d{2} \d{2}:00$/'],
    'week' => ['week', '/^\d{4}-\d{2}$/'],
]);

test('orders over time respects the number of days', function () {
    analyticsOrder($this->user, OrderStatus::NEW, 2);
    analyticsOrder($this->user, OrderStatus::NEW, 20);
    analyticsOrder($this->user, OrderStatus::NEW, 200);

    $count = fn (int $days) => array_sum(collect(
        $this->actingAs($this->user)->getJson(route('analytics.api.timeline', ['days' => $days]))->json()
    )->pluck('count')->all());

    expect($count(7))->toBe(1)
        ->and($count(30))->toBe(2)
        ->and($count(365))->toBe(3);
});

test('orders over time is ordered by period', function () {
    analyticsOrder($this->user, OrderStatus::NEW, 1);
    analyticsOrder($this->user, OrderStatus::NEW, 5);
    analyticsOrder($this->user, OrderStatus::NEW, 3);

    $periods = collect($this->actingAs($this->user)->getJson(route('analytics.api.timeline'))->json())->pluck('period')->all();

    expect($periods)->toBe(collect($periods)->sort()->values()->all());
});

test('orders over time rejects invalid parameters', function (array $query, string $field) {
    $this->actingAs($this->user)
        ->getJson(route('analytics.api.timeline', $query))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'unknown grouping' => [['period' => 'year'], 'period'],
    'zero days' => [['days' => 0], 'days'],
    'too many days' => [['days' => 366], 'days'],
    'days not a number' => [['days' => 'viele'], 'days'],
]);

// -------------------------------------------------------------- Top-Artikel

test('top items are summed per article and ordered by quantity', function () {
    $table = makeArticle(['name' => 'Tisch']);
    $chair = makeArticle(['name' => 'Stuhl']);

    foreach ([[$table, 2], [$chair, 10], [$table, 3]] as [$article, $quantity]) {
        analyticsOrderWithItem($this->user, $article, $quantity);
    }

    $response = $this->actingAs($this->user)->getJson(route('analytics.api.items'))->assertOk();

    expect($response->json('0.article_name'))->toBe('Stuhl')
        ->and((int) $response->json('0.total_quantity'))->toBe(10)
        ->and($response->json('1.article_name'))->toBe('Tisch')
        ->and((int) $response->json('1.total_quantity'))->toBe(5)
        ->and((int) $response->json('1.order_count'))->toBe(2);
});

test('top items can be limited', function () {
    foreach (range(1, 4) as $i) {
        analyticsOrderWithItem($this->user, makeArticle(['name' => "Artikel {$i}"]), $i);
    }

    $response = $this->actingAs($this->user)->getJson(route('analytics.api.items', ['limit' => 2]))->assertOk();

    expect($response->json())->toHaveCount(2)
        ->and($response->json('0.article_name'))->toBe('Artikel 4');
});

test('top items ignore old and deleted orders', function () {
    $article = makeArticle(['name' => 'Tisch']);

    analyticsOrderWithItem($this->user, $article, 4);
    analyticsOrderWithItem($this->user, $article, 4, 100);
    analyticsOrderWithItem($this->user, $article, 4)->delete();

    $response = $this->actingAs($this->user)->getJson(route('analytics.api.items'));

    expect($response->json())->toHaveCount(1)
        ->and((int) $response->json('0.total_quantity'))->toBe(4);
});

test('top items reject an invalid limit', function (mixed $limit) {
    $this->actingAs($this->user)
        ->getJson(route('analytics.api.items', ['limit' => $limit]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('limit');
})->with(['zero' => 0, 'too high' => 101, 'text' => 'alle']);

// ------------------------------------------------------------ Packleistung

test('packer performance counts finished orders per packer', function () {
    $anna = User::factory()->create(['name' => 'Anna Packer']);
    $ben = User::factory()->create(['name' => 'Ben Packer']);

    analyticsOrder($this->user, OrderStatus::PACKED, 1, ['packed_by' => $anna->id]);
    analyticsOrder($this->user, OrderStatus::DELIVERED, 2, ['packed_by' => $anna->id]);
    analyticsOrder($this->user, OrderStatus::IN_DELIVERY, 3, ['packed_by' => $ben->id]);

    $response = $this->actingAs($this->user)->getJson(route('analytics.api.packers'))->assertOk();

    expect(collect($response->json())->pluck('orders_packed', 'packer_name')->all())
        ->toBe(['Anna Packer' => 2, 'Ben Packer' => 1]);
});

test('packer performance ignores unfinished, unassigned, old and deleted orders', function () {
    $packer = User::factory()->create(['name' => 'Cora Packer']);

    analyticsOrder($this->user, OrderStatus::IN_PROGRESS, 1, ['packed_by' => $packer->id]);
    analyticsOrder($this->user, OrderStatus::PACKED, 1);
    analyticsOrder($this->user, OrderStatus::PACKED, 100, ['packed_by' => $packer->id]);
    analyticsOrder($this->user, OrderStatus::PACKED, 1, ['packed_by' => $packer->id])->delete();
    analyticsOrder($this->user, OrderStatus::PACKED, 1, ['packed_by' => $packer->id]);

    $response = $this->actingAs($this->user)->getJson(route('analytics.api.packers'));

    expect($response->json())->toHaveCount(1)
        ->and($response->json('0.orders_packed'))->toBe(1);
});

test('packer performance is empty without finished orders', function () {
    $this->actingAs($this->user)->getJson(route('analytics.api.packers'))->assertOk()->assertExactJson([]);
});

// ------------------------------------------------------------------- Rechte

test('the analytics routes need the view permission', function (string $route) {
    $outsider = userWithPermissions(['orders.view']);

    $this->actingAs($outsider)->getJson(route($route))->assertForbidden();
})->with(['analytics.index', 'analytics.api.status', 'analytics.api.timeline', 'analytics.api.items', 'analytics.api.packers']);

test('guests cannot read the analytics', function (string $route) {
    $this->getJson(route($route))->assertUnauthorized();
})->with(['analytics.index', 'analytics.api.status', 'analytics.api.timeline', 'analytics.api.items', 'analytics.api.packers']);
