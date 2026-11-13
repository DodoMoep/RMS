<?php

use App\Enums\OrderStatus;
use App\Exceptions\OrderWorkflowException;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

/**
 * Fachfehler zeigen ihren uebersetzten Text, unerwartete Fehler nur eine allgemeine
 * Meldung. Der Rohtext einer Ausnahme darf nie beim Benutzer ankommen (S2).
 */
function firstSessionError(): ?string
{
    return session('errors')?->first();
}

// ------------------------------------------------------------------ Fachfehler

test('packing more than the remaining quantity shows the translated message', function (string $locale) {
    $user = userWithPermissions(['orders.pack']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::IN_PROGRESS]);
    $item = $order->items()->first();

    $this->withSession(['locale' => $locale])
        ->actingAs($user)
        ->post(route('packing.pack-item', $item), ['quantity' => 99])
        ->assertSessionHasErrors();

    expect(firstSessionError())->toBe(__('packing.messages.exceeds_remaining', [], $locale))
        ->and($item->refresh()->quantity_packed)->toBe(0);
})->with(['de', 'en']);

test('completing an order with open items shows the translated message', function (string $locale) {
    $user = userWithPermissions(['orders.pack']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::IN_PROGRESS]);

    $this->withSession(['locale' => $locale])
        ->actingAs($user)
        ->post(route('packing.complete', $order))
        ->assertSessionHasErrors();

    expect(firstSessionError())->toBe(__('packing.messages.not_all_items_packed', [], $locale))
        ->and($order->refresh()->status)->toBe(OrderStatus::IN_PROGRESS);
})->with(['de', 'en']);

// ------------------------------------------------------------ Unerwartete Fehler

test('an unexpected error while packing shows only the generic message and is logged', function () {
    $user = userWithPermissions(['orders.pack']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::IN_PROGRESS]);
    $item = $order->items()->first();
    OrderItem::updating(fn () => throw new RuntimeException('geheimer Detailtext: SQLSTATE[HY000]'));
    Log::spy();

    $this->actingAs($user)->post(route('packing.pack-item', $item))->assertSessionHasErrors();

    expect(firstSessionError())->toBe(__('common.messages.unexpected_error'))
        ->and(firstSessionError())->not->toContain('geheimer')
        ->and(firstSessionError())->not->toContain('SQLSTATE');
    Log::shouldHaveReceived('error')->withArgs(
        fn ($message, $context) => $message === 'Packing error' && $context['exception'] instanceof RuntimeException
    )->once();
});

test('an unexpected error while unpacking shows only the generic message', function () {
    $user = userWithPermissions(['orders.pack']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::IN_PROGRESS]);
    $item = $order->items()->first();
    $item->update(['quantity_packed' => 2, 'is_packed' => true]);
    OrderItem::updating(fn () => throw new RuntimeException('geheimer Detailtext'));

    $this->actingAs($user)->delete(route('packing.unpack-item', $item))->assertSessionHasErrors();

    expect(firstSessionError())->toBe(__('common.messages.unexpected_error'))
        ->and(firstSessionError())->not->toContain('geheimer');
});

test('an unexpected error while completing an order shows only the generic message', function () {
    $user = userWithPermissions(['orders.pack']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::IN_PROGRESS]);
    $order->items()->update(['quantity_packed' => 2, 'is_packed' => true]);
    Order::updating(fn () => throw new RuntimeException('geheimer Detailtext'));

    $this->actingAs($user)->post(route('packing.complete', $order))->assertSessionHasErrors();

    expect(firstSessionError())->toBe(__('common.messages.unexpected_error'))
        ->and(firstSessionError())->not->toContain('geheimer');
});

test('an unexpected error while generating the delivery note shows only the generic message and is logged', function () {
    $user = userWithPermissions(['orders.print']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::PACKED]);
    Pdf::shouldReceive('loadView')->andThrow(new RuntimeException('geheimer Detailtext: /var/www/interner/pfad'));
    Log::spy();

    $this->actingAs($user)->post(route('delivery-notes.generate', $order))->assertSessionHasErrors();

    expect(firstSessionError())->toBe(__('common.messages.unexpected_error'))
        ->and(firstSessionError())->not->toContain('geheimer')
        ->and(firstSessionError())->not->toContain('/var/www');
    Log::shouldHaveReceived('error')->withArgs(
        fn ($message, $context) => $message === 'Delivery note error' && $context['order_id'] === $order->id
    )->once();
});

test('the error messages of the generic fallback exist in both languages', function () {
    expect(__('common.messages.unexpected_error', [], 'de'))->not->toBe('common.messages.unexpected_error')
        ->and(__('common.messages.unexpected_error', [], 'en'))->not->toBe('common.messages.unexpected_error')
        ->and(__('common.messages.unexpected_error', [], 'de'))->not->toBe(__('common.messages.unexpected_error', [], 'en'));
});

// ------------------------------------------------------- Die Fachausnahme selbst

test('the workflow exception delivers its text in the selected language', function () {
    $exception = new OrderWorkflowException('packing.messages.exceeds_remaining');

    app()->setLocale('de');
    expect($exception->userMessage())->toBe(__('packing.messages.exceeds_remaining', [], 'de'));

    app()->setLocale('en');
    expect($exception->userMessage())->toBe('You cannot pack more than the remaining quantity.');
});

test('the workflow exception keeps the key for logs and supports parameters', function () {
    $exception = new OrderWorkflowException('protocols.messages.signature_saved', ['role' => 'Mieter']);

    app()->setLocale('de');

    expect($exception->getMessage())->toBe('protocols.messages.signature_saved')
        ->and($exception->translationKey)->toBe('protocols.messages.signature_saved')
        ->and($exception->userMessage())->toBe('Unterschrift gespeichert. Ausstehend: Mieter.');
});

test('the order models throw the workflow exception with a translation key', function () {
    $user = userWithPermissions([]);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::IN_PROGRESS]);
    $item = $order->items()->first();

    $thrown = function (callable $action): ?OrderWorkflowException {
        try {
            $action();
        } catch (OrderWorkflowException $e) {
            return $e;
        }

        return null;
    };

    expect($thrown(fn () => $item->markAsPacked(99))?->translationKey)->toBe('packing.messages.exceeds_remaining')
        ->and($thrown(fn () => $order->lockOrder())?->translationKey)->toBe('packing.messages.not_all_items_packed');

    $order->update(['status' => OrderStatus::NEW]);

    expect($thrown(fn () => $order->lockOrder())?->translationKey)->toBe('packing.messages.order_not_packable')
        ->and($thrown(fn () => $order->markAsInDelivery())?->translationKey)->toBe('delivery_notes.messages.not_printable')
        ->and($thrown(fn () => $item->refresh()->markAsPacked())?->translationKey)->toBe('packing.messages.order_not_packable')
        ->and($thrown(fn () => $item->unpack())?->translationKey)->toBe('packing.messages.order_not_packable');
});
