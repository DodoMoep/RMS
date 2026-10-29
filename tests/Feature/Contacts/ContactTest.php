<?php

use App\Enums\ContactType;
use App\Models\Contact;
use App\Models\Order\Order;
use App\Models\Rental\Hall;
use App\Models\Rental\Rental;
use App\Models\User;

test('contact relations point to the order and rental models', function () {
    $contact = new Contact;

    expect($contact->orders()->getRelated())->toBeInstanceOf(Order::class)
        ->and($contact->rentals()->getRelated())->toBeInstanceOf(Rental::class);
});

test('contact list shows contacts with order and rental counts', function () {
    $user = userWithPermissions(['contacts.view']);
    $contact = makeContact(['name' => 'Listenkunde GmbH']);
    makeOrder($contact, $user);

    $this->actingAs($user)
        ->get(route('contacts.index'))
        ->assertOk()
        ->assertSee('Listenkunde GmbH');
});

test('contact detail shows orders of a customer', function () {
    $user = userWithPermissions(['contacts.view']);
    $contact = makeContact();
    $order = makeOrder($contact, $user);

    $this->actingAs($user)
        ->get(route('contacts.show', $contact))
        ->assertOk()
        ->assertSee($order->order_number);
});

test('contact detail shows rentals of a tenant', function () {
    $user = userWithPermissions(['contacts.view']);
    $contact = makeContact(['type' => ContactType::Tenant, 'name' => 'Mieter Testverein']);
    $hall = Hall::create(['name' => 'Testhalle']);
    Rental::create([
        'contact_id' => $contact->id,
        'hall_id' => $hall->id,
        'start' => now()->addDay(),
        'end' => now()->addDays(2),
        'status' => 'scheduled',
    ]);

    $this->actingAs($user)
        ->get(route('contacts.show', $contact))
        ->assertOk()
        ->assertSee('Testhalle');
});

test('contact with orders cannot be deleted', function () {
    $user = userWithPermissions(['contacts.view', 'contacts.delete']);
    $contact = makeContact();
    makeOrder($contact, $user);

    $this->actingAs($user)
        ->delete(route('contacts.destroy', $contact))
        ->assertSessionHasErrors();

    $this->assertModelExists($contact);
});

test('contact with rentals cannot be deleted', function () {
    $user = userWithPermissions(['contacts.delete']);
    $contact = makeContact(['type' => ContactType::Tenant]);
    $hall = Hall::create(['name' => 'Testhalle']);
    Rental::create([
        'contact_id' => $contact->id,
        'hall_id' => $hall->id,
        'start' => now()->addDay(),
        'end' => now()->addDays(2),
        'status' => 'scheduled',
    ]);

    $this->actingAs($user)
        ->delete(route('contacts.destroy', $contact))
        ->assertSessionHasErrors();

    $this->assertModelExists($contact);
});

test('contact without relations can be deleted', function () {
    $user = userWithPermissions(['contacts.delete']);
    $contact = makeContact();

    $this->actingAs($user)
        ->delete(route('contacts.destroy', $contact))
        ->assertRedirect(route('contacts.index'));

    $this->assertModelMissing($contact);
});

test('customer numbers stay unique when contacts are created within the same second', function () {
    $numbers = collect(range(1, 5))
        ->map(fn ($i) => makeContact(['name' => "Serienkunde {$i}"])->customer_number);

    expect($numbers->unique())->toHaveCount(5)
        ->and($numbers->first())->toMatch('/^CUS-\d{4}-0001$/')
        ->and($numbers->last())->toMatch('/^CUS-\d{4}-0005$/');
});

test('tenants do not receive a customer number', function () {
    expect(makeContact(['type' => ContactType::Tenant])->customer_number)->toBeNull();
});

test('a tenant is stored without contact person and address', function () {
    $user = userWithPermissions(['contacts.create']);

    $this->actingAs($user)->post(route('contacts.store'), [
        'type' => 'tenant',
        'name' => 'Nur Mieter',
        'contact_person_name' => 'Wird ignoriert',
        'street' => 'Wird ignoriert 1',
        'city' => 'Ignorierstadt',
    ]);

    $contact = Contact::where('name', 'Nur Mieter')->firstOrFail();

    expect($contact->contact_person_name)->toBeNull()
        ->and($contact->street)->toBeNull()
        ->and($contact->city)->toBeNull()
        ->and($contact->customer_number)->toBeNull();
});

test('a customer is stored with contact person and address', function () {
    $user = userWithPermissions(['contacts.create']);

    $this->actingAs($user)->post(route('contacts.store'), [
        'type' => 'customer',
        'name' => 'Nur Kunde',
        'contact_person_name' => 'Frau Beispiel',
        'street' => 'Kundenweg 3',
        'zip_code' => '11111',
        'city' => 'Kundenstadt',
    ]);

    $contact = Contact::where('name', 'Nur Kunde')->firstOrFail();

    expect($contact->contact_person_name)->toBe('Frau Beispiel')
        ->and($contact->street)->toBe('Kundenweg 3')
        ->and($contact->zip_code)->toBe('11111')
        ->and($contact->city)->toBe('Kundenstadt')
        ->and($contact->customer_number)->not->toBeNull();
});

test('a tenant who becomes a customer receives a customer number and the address is saved', function () {
    $user = userWithPermissions(['contacts.edit']);
    $contact = makeContact(['type' => ContactType::Tenant, 'name' => 'Wird Kunde', 'street' => null, 'zip_code' => null, 'city' => null]);

    expect($contact->customer_number)->toBeNull();

    $this->actingAs($user)->put(route('contacts.update', $contact), [
        'type' => 'both',
        'name' => 'Wird Kunde',
        'street' => 'Neuer Weg 5',
        'zip_code' => '22222',
        'city' => 'Neustadt',
    ]);

    $contact->refresh();

    expect($contact->customer_number)->toMatch('/^CUS-\d{4}-\d{4}$/')
        ->and($contact->street)->toBe('Neuer Weg 5')
        ->and($contact->city)->toBe('Neustadt');
});

test('the contact type is validated', function () {
    $user = userWithPermissions(['contacts.create']);

    $this->actingAs($user)
        ->post(route('contacts.store'), ['type' => 'unbekannt', 'name' => 'Fehler'])
        ->assertSessionHasErrors('type');
});

test('contacts are not accessible without permission', function () {
    $user = User::factory()->create();
    $contact = makeContact();

    $this->actingAs($user)->get(route('contacts.index'))->assertForbidden();
    $this->actingAs($user)->get(route('contacts.show', $contact))->assertForbidden();
});
