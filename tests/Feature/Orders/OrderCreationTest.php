<?php

use App\Enums\ContactType;
use App\Enums\OrderStatus;
use App\Models\Contact;
use App\Models\Order\Order;

/**
 * Auftragsanlage (OrderController::create und store).
 */
function orderPayload(array $overrides = []): array
{
    $article = $overrides['__article'] ?? makeArticle(['name' => 'Bierzeltgarnitur']);
    unset($overrides['__article']);

    return array_merge([
        'customer_type' => 'existing',
        'contact_id' => makeContact()->id,
        'delivery_date' => now()->addDays(3)->toDateString(),
        'delivery_type' => 'delivery',
        'notes' => 'Bitte vormittags',
        'items' => [
            ['article_id' => $article->id, 'quantity' => 4, 'notes' => 'mit Tischdecke'],
        ],
    ], $overrides);
}

function individualCustomerPayload(array $overrides = []): array
{
    return orderPayload(array_merge([
        'customer_type' => 'individual',
        'contact_id' => null,
        'customer_name' => 'Einzelkunde Beispiel',
        'contact_person_name' => 'Erika Beispiel',
        'customer_email' => 'einzel@example.test',
        'customer_phone' => '012345',
        'street' => 'Musterweg 5',
        'zip_code' => '54321',
        'city' => 'Beispielstadt',
        'address_notes' => 'Hinterhof',
    ], $overrides));
}

beforeEach(function () {
    $this->user = userWithPermissions(['orders.view', 'orders.create']);
});

// ------------------------------------------------------------ Bestandskunde

test('an order for an existing customer is created with a snapshot of the articles', function () {
    $article = makeArticle(['name' => 'Bierzeltgarnitur', 'sku' => 'BZG-1', 'price' => 9.90]);

    $response = $this->actingAs($this->user)
        ->post(route('orders.store'), orderPayload(['__article' => $article]));

    $order = Order::with('items')->firstOrFail();

    $response->assertRedirect(route('orders.show', $order))
        ->assertSessionHas('ok', __('orders.messages.order_created'));

    expect($order->status)->toBe(OrderStatus::NEW)
        ->and($order->created_by)->toBe($this->user->id)
        ->and($order->delivery_type)->toBe('delivery')
        ->and($order->notes)->toBe('Bitte vormittags')
        ->and($order->order_number)->toMatch('/^ORD-\d{4}-\d{4}$/')
        ->and($order->items)->toHaveCount(1)
        ->and($order->items[0]->article_id)->toBe($article->id)
        ->and($order->items[0]->article_name)->toBe('Bierzeltgarnitur')
        ->and($order->items[0]->article_sku)->toBe('BZG-1')
        ->and((string) $order->items[0]->article_price)->toBe('9.90')
        ->and($order->items[0]->quantity_ordered)->toBe(4)
        ->and($order->items[0]->notes)->toBe('mit Tischdecke')
        ->and($order->items[0]->quantity_packed)->toBe(0);
});

test('the snapshot keeps the article data when the article changes later', function () {
    $article = makeArticle(['name' => 'Alter Name', 'price' => 5]);
    $this->actingAs($this->user)->post(route('orders.store'), orderPayload(['__article' => $article]));

    $article->update(['name' => 'Neuer Name', 'price' => 99]);

    $item = Order::firstOrFail()->items()->firstOrFail();

    expect($item->article_name)->toBe('Alter Name')
        ->and((string) $item->article_price)->toBe('5.00');
});

test('a new order writes the creation event to the history', function () {
    $this->actingAs($this->user)->post(route('orders.store'), orderPayload());

    $order = Order::firstOrFail();
    $event = $order->history()->firstOrFail();

    expect($event->event_type)->toBe('order_created')
        ->and($event->user_id)->toBe($this->user->id)
        ->and($event->description)->toContain($order->order_number);
});

test('consecutive orders get consecutive numbers', function () {
    $this->actingAs($this->user)->post(route('orders.store'), orderPayload());
    $this->actingAs($this->user)->post(route('orders.store'), orderPayload());

    $numbers = Order::orderBy('order_number')->pluck('order_number')->all();
    $year = now()->format('Y');

    expect($numbers)->toBe(["ORD-{$year}-0001", "ORD-{$year}-0002"]);
});

test('an order with several items stores every item', function () {
    $first = makeArticle(['name' => 'Tisch']);
    $second = makeArticle(['name' => 'Stuhl']);

    $this->actingAs($this->user)->post(route('orders.store'), orderPayload([
        'items' => [
            ['article_id' => $first->id, 'quantity' => 2],
            ['article_id' => $second->id, 'quantity' => 10],
        ],
    ]));

    expect(Order::firstOrFail()->items()->orderBy('article_name')->pluck('quantity_ordered', 'article_name')->all())
        ->toBe(['Stuhl' => 10, 'Tisch' => 2]);
});

test('a delivery to an existing customer without a full address is rejected', function (array $address) {
    $contact = makeContact($address);

    $this->actingAs($this->user)
        ->post(route('orders.store'), orderPayload(['contact_id' => $contact->id]))
        ->assertSessionHasErrors(['contact_id' => __('orders.messages.customer_needs_address_for_delivery')]);

    expect(Order::count())->toBe(0);
})->with([
    'no street' => [['street' => null]],
    'no zip code' => [['zip_code' => null]],
    'no city' => [['city' => null]],
]);

test('a pickup for an existing customer without an address is allowed', function () {
    $contact = makeContact(['street' => null, 'zip_code' => null, 'city' => null]);

    $this->actingAs($this->user)
        ->post(route('orders.store'), orderPayload(['contact_id' => $contact->id, 'delivery_type' => 'pickup']))
        ->assertSessionHasNoErrors();

    expect(Order::count())->toBe(1);
});

// -------------------------------------------------------------- Einzelkunde

test('an order for an individual customer creates a customer contact', function () {
    $this->actingAs($this->user)
        ->post(route('orders.store'), individualCustomerPayload())
        ->assertSessionHasNoErrors();

    $contact = Contact::where('name', 'Einzelkunde Beispiel')->firstOrFail();
    $order = Order::firstOrFail();

    expect($contact->type)->toBe(ContactType::Customer)
        ->and($contact->contact_person_name)->toBe('Erika Beispiel')
        ->and($contact->email)->toBe('einzel@example.test')
        ->and($contact->phone)->toBe('012345')
        ->and($contact->street)->toBe('Musterweg 5')
        ->and($contact->zip_code)->toBe('54321')
        ->and($contact->city)->toBe('Beispielstadt')
        ->and($contact->address_notes)->toBe('Hinterhof')
        ->and($contact->is_active)->toBeTrue()
        ->and($contact->customer_number)->toMatch('/^CUS-\d{4}-\d{4}$/')
        ->and($order->contact_id)->toBe($contact->id);
});

test('a delivery to an individual customer needs street, zip code and city', function (string $field) {
    $this->actingAs($this->user)
        ->post(route('orders.store'), individualCustomerPayload([$field => '']))
        ->assertSessionHasErrors($field);

    expect(Order::count())->toBe(0)
        ->and(Contact::where('name', 'Einzelkunde Beispiel')->exists())->toBeFalse();
})->with(['street', 'zip_code', 'city']);

test('a pickup for an individual customer needs no address', function () {
    $this->actingAs($this->user)
        ->post(route('orders.store'), individualCustomerPayload([
            'delivery_type' => 'pickup',
            'street' => '',
            'zip_code' => '',
            'city' => '',
        ]))
        ->assertSessionHasNoErrors();

    expect(Order::count())->toBe(1);
});

test('an individual customer needs a name', function () {
    $this->actingAs($this->user)
        ->post(route('orders.store'), individualCustomerPayload(['customer_name' => '']))
        ->assertSessionHasErrors('customer_name');

    expect(Order::count())->toBe(0);
});

test('an existing order contact must be a customer or both', function (ContactType $type, bool $accepted) {
    $contact = makeContact(['type' => $type, 'name' => 'Typ '.$type->value]);

    $response = $this->actingAs($this->user)
        ->post(route('orders.store'), orderPayload(['contact_id' => $contact->id]));

    if ($accepted) {
        $response->assertSessionHasNoErrors();
    } else {
        $response->assertSessionHasErrors('contact_id');
    }

    expect(Order::where('contact_id', $contact->id)->count())->toBe($accepted ? 1 : 0);
})->with([
    'customer' => [ContactType::Customer, true],
    'both' => [ContactType::Both, true],
    'tenant only' => [ContactType::Tenant, false],
]);

test('an existing customer needs a contact id', function () {
    $this->actingAs($this->user)
        ->post(route('orders.store'), orderPayload(['contact_id' => null]))
        ->assertSessionHasErrors('contact_id');
});

// -------------------------------------------------------------- Validierung

test('the delivery date must lie after today', function (int $daysFromToday) {
    $this->actingAs($this->user)
        ->post(route('orders.store'), orderPayload(['delivery_date' => now()->addDays($daysFromToday)->toDateString()]))
        ->assertSessionHasErrors('delivery_date');

    expect(Order::count())->toBe(0);
})->with(['today' => [0], 'yesterday' => [-1]]);

test('tomorrow is accepted as delivery date', function () {
    $this->actingAs($this->user)
        ->post(route('orders.store'), orderPayload(['delivery_date' => now()->addDay()->toDateString()]))
        ->assertSessionHasNoErrors();

    expect(Order::count())->toBe(1);
});

test('invalid order data is rejected', function (array $overrides, string $errorField) {
    $this->actingAs($this->user)
        ->post(route('orders.store'), orderPayload($overrides))
        ->assertSessionHasErrors($errorField);

    expect(Order::count())->toBe(0);
})->with([
    'no items' => [['items' => []], 'items'],
    'unknown article' => [['items' => [['article_id' => '00000000-0000-0000-0000-000000000000', 'quantity' => 1]]], 'items.0.article_id'],
    'quantity zero' => [['items' => [['article_id' => null, 'quantity' => 0]]], 'items.0.quantity'],
    'quantity not a number' => [['items' => [['article_id' => null, 'quantity' => 'viele']]], 'items.0.quantity'],
    'unknown customer type' => [['customer_type' => 'firma'], 'customer_type'],
    'unknown delivery type' => [['delivery_type' => 'drohne'], 'delivery_type'],
    'missing delivery date' => [['delivery_date' => null], 'delivery_date'],
    'unknown contact' => [['contact_id' => '00000000-0000-0000-0000-000000000000'], 'contact_id'],
    'invalid email' => [['customer_type' => 'individual', 'contact_id' => null, 'customer_name' => 'X', 'customer_email' => 'keine-mail', 'street' => 'a', 'zip_code' => '1', 'city' => 'b'], 'customer_email'],
]);

// ------------------------------------------------------------- Formular, Rechte

test('the creation form only offers active articles and active customers', function () {
    makeArticle(['name' => 'Aktiver Artikel']);
    makeArticle(['name' => 'Inaktiver Artikel', 'is_active' => false]);
    makeContact(['name' => 'Aktiver Kunde']);
    makeContact(['name' => 'Inaktiver Kunde', 'is_active' => false]);
    makeContact(['name' => 'Nur Mieter', 'type' => ContactType::Tenant]);

    $response = $this->actingAs($this->user)->get(route('orders.create'))->assertOk();

    $response->assertSee('Aktiver Artikel')
        ->assertDontSee('Inaktiver Artikel')
        ->assertSee('Aktiver Kunde')
        ->assertDontSee('Inaktiver Kunde')
        ->assertDontSee('Nur Mieter');
});

test('creating an order requires the create permission', function () {
    $viewer = userWithPermissions(['orders.view']);

    $this->actingAs($viewer)->get(route('orders.create'))->assertForbidden();
    $this->actingAs($viewer)->post(route('orders.store'), orderPayload())->assertForbidden();

    expect(Order::count())->toBe(0);
});

test('guests are sent to the login when creating an order', function () {
    $this->post(route('orders.store'), orderPayload())->assertRedirect(route('login'));

    expect(Order::count())->toBe(0);
});
