<?php

use App\Enums\OrderStatus;
use App\Models\Order\Order;
use App\Models\Order\OrderHistory;

/**
 * Die Auftragshistorie wird beim Anzeigen in der gewaehlten Sprache aufgebaut. Gespeichert
 * bleibt der englische Text, er dient als Rueckfall fuer alte oder unbekannte Eintraege.
 */
function historyEntry(string $type, array $metadata = [], string $description = 'Gespeicherter Text'): OrderHistory
{
    return new OrderHistory(['event_type' => $type, 'metadata' => $metadata, 'description' => $description]);
}

/**
 * Ein Auftrag, an dem jeder Ereignistyp ueber den echten Weg entstanden ist (ausser dem
 * Lieferschein, dessen PDF hier keine Rolle spielt).
 */
function orderWithFullHistory($test): Order
{
    $user = userWithPermissions(['orders.view', 'orders.view-history']);
    $test->actingAs($user);

    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::NEW]);
    $order->update(['status' => OrderStatus::IN_PROGRESS]);

    $item = $order->items()->firstOrFail();
    $item->update(['article_name' => 'Bierzeltgarnitur']);
    $item->refresh()->markAsPacked(1);
    $item->refresh()->unpack();
    $item->refresh()->markAsPacked();

    $order->refresh()->lockOrder();
    $order->refresh()->logHistory('delivery_note_generated', 'Delivery note generated');
    $order->refresh()->markAsInDelivery();

    return $order;
}

// ------------------------------------------------------------- Historienseite

test('the history page is shown in german', function () {
    $order = orderWithFullHistory($this);

    $this->withSession(['locale' => 'de'])->get(route('orders.history', $order))
        ->assertOk()
        ->assertSee("Bestellung {$order->order_number} erstellt")
        ->assertSee('Status von "Neu" auf "In Bearbeitung" geändert')
        ->assertSee('Artikel "Bierzeltgarnitur" verpackt (1 Stück)')
        ->assertSee('Artikel "Bierzeltgarnitur" entpackt')
        ->assertSee('Bestellung abgeschlossen und gesperrt')
        ->assertSee('Lieferschein erstellt')
        ->assertSee('Lieferschein gedruckt, Bestellung in Zustellung gesetzt')
        ->assertSee('Status von "In Bearbeitung" auf "Verpackt" geändert')
        ->assertDontSee("Order {$order->order_number} created")
        ->assertDontSee('Status changed from')
        ->assertDontSee('packed (');
});

test('the history page is shown in english', function () {
    $order = orderWithFullHistory($this);

    $this->withSession(['locale' => 'en'])->get(route('orders.history', $order))
        ->assertOk()
        ->assertSee("Order {$order->order_number} created")
        ->assertSee('Status changed from "New" to "In Progress"')
        ->assertSee('Item "Bierzeltgarnitur" packed (1 units)')
        ->assertSee('Item "Bierzeltgarnitur" unpacked')
        ->assertSee('Order completed and locked')
        ->assertSee('Delivery note generated')
        ->assertSee('Delivery note printed, order set to in delivery')
        ->assertDontSee('Bestellung')
        ->assertDontSee('Lieferschein');
});

test('the event type badges are translated', function () {
    $order = orderWithFullHistory($this);

    $this->withSession(['locale' => 'de'])->get(route('orders.history', $order))
        ->assertSee('Bestellung erstellt')
        ->assertSee('Status geändert')
        ->assertSee('Artikel verpackt')
        ->assertSee('Artikel entpackt')
        ->assertSee('Bestellung abgeschlossen')
        ->assertSee('Lieferschein gedruckt');

    $this->withSession(['locale' => 'en'])->get(route('orders.history', $order))
        ->assertSee('Order Created')
        ->assertSee('Status Changed')
        ->assertSee('Delivery Note Printed');
});

test('an entry with an unknown type shows the stored text and the raw type', function () {
    $user = userWithPermissions(['orders.view', 'orders.view-history']);
    $order = makeOrder(makeContact(), $user);
    $order->history()->create([
        'event_type' => 'something_else', 'description' => 'Frueherer Sondertext', 'metadata' => [],
    ]);

    $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('orders.history', $order))
        ->assertOk()
        ->assertSee('Frueherer Sondertext')
        ->assertSee('something_else');
});

// ------------------------------------------------------ localizedDescription

test('the description is built from the type and the stored values', function (string $locale, string $type, array $metadata, string $expected) {
    app()->setLocale($locale);

    expect(historyEntry($type, $metadata)->localizedDescription('ORD-2026-0007'))->toBe($expected);
})->with([
    'created de' => ['de', 'order_created', [], 'Bestellung ORD-2026-0007 erstellt'],
    'created en' => ['en', 'order_created', [], 'Order ORD-2026-0007 created'],
    'deleted de' => ['de', 'order_deleted', [], 'Bestellung ORD-2026-0007 gelöscht'],
    'deleted en' => ['en', 'order_deleted', [], 'Order ORD-2026-0007 deleted'],
    'status de' => ['de', 'status_changed', ['old_status' => 'packed', 'new_status' => 'in_delivery'], 'Status von "Verpackt" auf "In Zustellung" geändert'],
    'status en' => ['en', 'status_changed', ['old_status' => 'packed', 'new_status' => 'in_delivery'], 'Status changed from "Packed" to "In Delivery"'],
    'packed de' => ['de', 'item_packed', ['item_name' => 'Tisch', 'quantity_packed' => 3], 'Artikel "Tisch" verpackt (3 Stück)'],
    'packed en' => ['en', 'item_packed', ['item_name' => 'Tisch', 'quantity_packed' => 3], 'Item "Tisch" packed (3 units)'],
    'unpacked de' => ['de', 'item_unpacked', ['item_name' => 'Tisch'], 'Artikel "Tisch" entpackt'],
    'completed en' => ['en', 'order_completed', [], 'Order completed and locked'],
    'note generated de' => ['de', 'delivery_note_generated', [], 'Lieferschein erstellt'],
    'note printed en' => ['en', 'delivery_note_printed', [], 'Delivery note printed, order set to in delivery'],
]);

test('the stored text is the fallback when values are missing', function (string $type, array $metadata, ?string $orderNumber) {
    app()->setLocale('de');

    expect(historyEntry($type, $metadata, 'Stored english text')->localizedDescription($orderNumber))
        ->toBe('Stored english text');
})->with([
    'created without number' => ['order_created', [], null],
    'status without values' => ['status_changed', [], 'ORD-1'],
    'status with only one value' => ['status_changed', ['old_status' => 'new'], 'ORD-1'],
    'packed without quantity' => ['item_packed', ['item_name' => 'Tisch'], 'ORD-1'],
    'unpacked without name' => ['item_unpacked', [], 'ORD-1'],
    'unknown type' => ['order_updated', ['a' => 1], 'ORD-1'],
]);

test('the event type label falls back to the raw type', function () {
    app()->setLocale('de');

    expect(historyEntry('item_packed')->localizedEventType())->toBe('Artikel verpackt')
        ->and(historyEntry('gibt_es_nicht')->localizedEventType())->toBe('gibt_es_nicht');
});

test('every logged event type has a description and a label in both languages', function (string $type) {
    foreach (['de', 'en'] as $locale) {
        expect(__("orders.history_events.{$type}", [], $locale))->not->toBe("orders.history_events.{$type}")
            ->and(__("orders.history_event_types.{$type}", [], $locale))->not->toBe("orders.history_event_types.{$type}");
    }
})->with(['order_created', 'order_deleted', 'status_changed', 'item_packed', 'item_unpacked', 'order_completed', 'delivery_note_generated', 'delivery_note_printed']);

test('the stored description stays english and unchanged', function () {
    $order = orderWithFullHistory($this);

    $created = $order->history()->where('event_type', 'order_created')->firstOrFail();

    expect($created->description)->toBe("Order {$order->order_number} created");
});
