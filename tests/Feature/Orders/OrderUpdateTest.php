<?php

use App\Enums\OrderStatus;
use App\Models\Contact;
use App\Models\Order\Order;

/**
 * Auftrag bearbeiten (OrderController::edit und update).
 */
function updatePayload(Order $order, array $overrides = []): array
{
    $item = $order->items()->firstOrFail();

    return array_merge([
        'delivery_date' => now()->addDays(5)->toDateString(),
        'delivery_type' => 'delivery',
        'notes' => 'Neue Notiz',
        'items' => [
            ['id' => $item->id, 'article_id' => makeArticle()->id, 'quantity' => 7, 'notes' => 'neu'],
        ],
    ], $overrides);
}

beforeEach(function () {
    $this->user = userWithPermissions(['orders.view', 'orders.edit']);
    $this->order = makeOrder(makeContact(), $this->user, ['status' => OrderStatus::IN_PROGRESS]);
});

// ------------------------------------------------------------------ Aendern

test('an open order can be changed', function (OrderStatus $status) {
    $this->order->update(['status' => $status]);

    $this->actingAs($this->user)
        ->put(route('orders.update', $this->order), updatePayload($this->order))
        ->assertRedirect(route('orders.show', $this->order))
        ->assertSessionHas('ok', __('orders.messages.order_updated'));

    $this->order->refresh();
    $item = $this->order->items()->firstOrFail();

    expect($this->order->delivery_date->toDateString())->toBe(now()->addDays(5)->toDateString())
        ->and($this->order->notes)->toBe('Neue Notiz')
        ->and($item->quantity_ordered)->toBe(7)
        ->and($item->notes)->toBe('neu');
})->with([OrderStatus::NEW, OrderStatus::IN_PROGRESS]);

test('changing the delivery type to pickup needs no address', function () {
    $this->order->contact->update(['street' => null, 'zip_code' => null, 'city' => null]);
    $this->order->update(['delivery_type' => 'pickup']);

    $this->actingAs($this->user)
        ->put(route('orders.update', $this->order), updatePayload($this->order, ['delivery_type' => 'pickup']))
        ->assertSessionHasNoErrors();

    expect($this->order->refresh()->notes)->toBe('Neue Notiz');
});

test('a new item is added with the article data', function () {
    $article = makeArticle(['name' => 'Neuer Artikel', 'sku' => 'NEU-1', 'price' => 3.5]);
    $existing = $this->order->items()->firstOrFail();

    $this->actingAs($this->user)->put(route('orders.update', $this->order), updatePayload($this->order, [
        'items' => [
            ['id' => $existing->id, 'article_id' => $article->id, 'quantity' => 2],
            ['article_id' => $article->id, 'quantity' => 5, 'notes' => 'zusaetzlich'],
        ],
    ]))->assertSessionHasNoErrors();

    $added = $this->order->items()->where('article_name', 'Neuer Artikel')->firstOrFail();

    expect($this->order->items()->count())->toBe(2)
        ->and($added->article_sku)->toBe('NEU-1')
        ->and((string) $added->article_price)->toBe('3.50')
        ->and($added->quantity_ordered)->toBe(5)
        ->and($added->notes)->toBe('zusaetzlich');
});

test('an item that is no longer sent is removed when nothing has been packed', function () {
    $article = makeArticle();
    $kept = $this->order->items()->firstOrFail();
    $removed = $this->order->items()->create(['article_name' => 'Entfaellt', 'quantity_ordered' => 1]);

    $this->actingAs($this->user)->put(route('orders.update', $this->order), updatePayload($this->order, [
        'items' => [['id' => $kept->id, 'article_id' => $article->id, 'quantity' => 2]],
    ]))->assertSessionHasNoErrors();

    expect($this->order->items()->pluck('id')->all())->toBe([$kept->id])
        ->and($removed->fresh())->toBeNull();
});

test('an item with packing progress is kept and not changed', function (array $progress) {
    $article = makeArticle();
    $kept = $this->order->items()->firstOrFail();
    $packed = $this->order->items()->create(array_merge(
        ['article_name' => 'Teilweise gepackt', 'quantity_ordered' => 4], $progress
    ));

    // Die gepackte Position fehlt in der Anfrage und die Menge der anderen waere aenderbar
    $this->actingAs($this->user)->put(route('orders.update', $this->order), updatePayload($this->order, [
        'items' => [['id' => $kept->id, 'article_id' => $article->id, 'quantity' => 9]],
    ]))->assertSessionHasNoErrors();

    expect($packed->fresh())->not->toBeNull()
        ->and($packed->fresh()->quantity_ordered)->toBe(4);
})->with([
    'partially packed' => [['quantity_packed' => 2]],
    'fully packed' => [['quantity_packed' => 4, 'is_packed' => true]],
]);

test('the quantity of a packed item cannot be changed', function (array $progress) {
    $article = makeArticle();
    $packed = $this->order->items()->create(array_merge(
        ['article_name' => 'Gepackt', 'quantity_ordered' => 4, 'notes' => 'alt'], $progress
    ));

    $this->actingAs($this->user)->put(route('orders.update', $this->order), updatePayload($this->order, [
        'items' => [
            ['id' => $this->order->items()->where('article_name', 'Testartikel')->value('id'), 'article_id' => $article->id, 'quantity' => 2],
            ['id' => $packed->id, 'article_id' => $article->id, 'quantity' => 1, 'notes' => 'neu'],
        ],
    ]))->assertSessionHasNoErrors();

    expect($packed->fresh()->quantity_ordered)->toBe(4)
        ->and($packed->fresh()->notes)->toBe('alt');
})->with([
    'partially packed' => [['quantity_packed' => 1]],
    'fully packed' => [['quantity_packed' => 4, 'is_packed' => true]],
]);

test('an item id from another order is ignored', function () {
    $other = makeOrder(makeContact(), $this->user);
    $foreign = $other->items()->firstOrFail();
    $article = makeArticle();

    $this->actingAs($this->user)->put(route('orders.update', $this->order), updatePayload($this->order, [
        'items' => [['id' => $foreign->id, 'article_id' => $article->id, 'quantity' => 99]],
    ]))->assertSessionHasNoErrors();

    expect($foreign->fresh()->quantity_ordered)->toBe(2);
});

// ------------------------------------------------------------------ Sperren

test('an order beyond packing cannot be edited or changed', function (OrderStatus $status) {
    $this->order->update(['status' => $status]);

    $this->actingAs($this->user)->get(route('orders.edit', $this->order))
        ->assertRedirect();
    expect(session('errors')->first())->toBe(__('orders.messages.cannot_edit'));

    $this->actingAs($this->user)->put(route('orders.update', $this->order), updatePayload($this->order))
        ->assertRedirect();
    expect(session('errors')->first())->toBe(__('orders.messages.cannot_edit_status_changed'))
        ->and($this->order->refresh()->notes)->not->toBe('Neue Notiz')
        ->and($this->order->items()->firstOrFail()->quantity_ordered)->toBe(2);
})->with([OrderStatus::PACKED, OrderStatus::IN_DELIVERY, OrderStatus::DELIVERED]);

test('the status is read fresh so a change by another user is noticed', function () {
    // Das Model im Controller ist frisch geladen: Der Status in der Datenbank zaehlt
    Order::whereKey($this->order->id)->update(['status' => OrderStatus::PACKED->value]);

    $this->actingAs($this->user)->put(route('orders.update', $this->order), updatePayload($this->order));

    expect(session('errors')->first())->toBe(__('orders.messages.cannot_edit_status_changed'));
});

test('a differing expected status sends the user back to the order', function () {
    $this->actingAs($this->user)
        ->put(route('orders.update', $this->order), updatePayload($this->order, ['expected_status' => 'new']))
        ->assertRedirect(route('orders.show', $this->order))
        ->assertSessionHas('error', __('orders.messages.order_changed_by_other_user'));

    expect($this->order->refresh()->notes)->not->toBe('Neue Notiz');
});

test('a matching expected status lets the change through', function () {
    $this->actingAs($this->user)
        ->put(route('orders.update', $this->order), updatePayload($this->order, ['expected_status' => 'in_progress']))
        ->assertSessionHasNoErrors();

    expect($this->order->refresh()->notes)->toBe('Neue Notiz');
});

test('a delivery needs a customer with a full address', function () {
    $contact = Contact::findOrFail($this->order->contact_id);
    $contact->update(['street' => null]);

    $this->actingAs($this->user)
        ->put(route('orders.update', $this->order), updatePayload($this->order, ['delivery_type' => 'delivery']))
        ->assertSessionHasErrors(['delivery_type' => __('orders.messages.customer_needs_address_for_delivery')]);

    expect($this->order->refresh()->notes)->not->toBe('Neue Notiz');
});

// -------------------------------------------------------------- Validierung

test('today is accepted but the past is rejected as delivery date when editing', function () {
    $this->actingAs($this->user)
        ->put(route('orders.update', $this->order), updatePayload($this->order, ['delivery_date' => now()->toDateString()]))
        ->assertSessionHasNoErrors();

    $this->actingAs($this->user)
        ->put(route('orders.update', $this->order), updatePayload($this->order, ['delivery_date' => now()->subDay()->toDateString()]))
        ->assertSessionHasErrors('delivery_date');
});

test('invalid data is rejected when editing', function (array $overrides, string $errorField) {
    $this->actingAs($this->user)
        ->put(route('orders.update', $this->order), updatePayload($this->order, $overrides))
        ->assertSessionHasErrors($errorField);

    expect($this->order->refresh()->notes)->not->toBe('Neue Notiz');
})->with([
    'no items' => [['items' => []], 'items'],
    'quantity zero' => [['items' => [['article_id' => null, 'quantity' => 0]]], 'items.0.quantity'],
    'unknown delivery type' => [['delivery_type' => 'drohne'], 'delivery_type'],
    'missing delivery date' => [['delivery_date' => null], 'delivery_date'],
    'unknown item id' => [['items' => [['id' => '00000000-0000-0000-0000-000000000000', 'article_id' => null, 'quantity' => 1]]], 'items.0.id'],
]);

// -------------------------------------------------------- Formular und Rechte

test('the edit form shows the current order', function () {
    $article = makeArticle(['name' => 'Formulartisch']);
    $this->order->update(['notes' => 'Hinweis fuer das Formular']);
    $this->order->items()->create(['article_id' => $article->id, 'article_name' => 'Formulartisch', 'quantity_ordered' => 3]);

    $this->actingAs($this->user)
        ->get(route('orders.edit', $this->order))
        ->assertOk()
        ->assertSee('Hinweis fuer das Formular')
        ->assertSee('Formulartisch');
});

test('editing requires the edit permission', function () {
    $viewer = userWithPermissions(['orders.view']);

    $this->actingAs($viewer)->get(route('orders.edit', $this->order))->assertForbidden();
    $this->actingAs($viewer)->put(route('orders.update', $this->order), updatePayload($this->order))->assertForbidden();

    expect($this->order->refresh()->notes)->not->toBe('Neue Notiz');
});

test('a deleted order cannot be edited', function () {
    $this->order->delete();

    $this->actingAs($this->user)->get(route('orders.edit', $this->order->id))->assertNotFound();
    $this->actingAs($this->user)->put(route('orders.update', $this->order->id), updatePayload($this->order))->assertNotFound();
});
