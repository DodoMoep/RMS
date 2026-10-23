<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Legt einen verifizierten Benutzer mit genau den genannten Rechten an.
 *
 * @param  list<string>  $permissions
 */
function userWithPermissions(array $permissions): App\Models\User
{
    $user = App\Models\User::factory()->create();

    foreach ($permissions as $name) {
        Spatie\Permission\Models\Permission::findOrCreate($name, 'web');
    }

    $user->givePermissionTo($permissions);

    return $user;
}

/**
 * Legt einen Kunden mit Lieferadresse an (erfundene Testdaten).
 *
 * @param  array<string, mixed>  $overrides
 */
function makeContact(array $overrides = []): App\Models\Contact
{
    return App\Models\Contact::create(array_merge([
        'type' => App\Enums\ContactType::Customer,
        'name' => 'Muster GmbH',
        'email' => 'kontakt@example.test',
        'street' => 'Teststrasse 1',
        'zip_code' => '12345',
        'city' => 'Testhausen',
        'is_active' => true,
    ], $overrides));
}

/**
 * Legt einen Auftrag mit einer Position an.
 *
 * @param  array<string, mixed>  $overrides
 */
function makeOrder(App\Models\Contact $contact, App\Models\User $creator, array $overrides = []): App\Models\Order\Order
{
    $order = App\Models\Order\Order::create(array_merge([
        'contact_id' => $contact->id,
        'delivery_date' => now()->addDays(3)->toDateString(),
        'delivery_type' => 'delivery',
        'status' => App\Enums\OrderStatus::IN_PROGRESS,
        'created_by' => $creator->id,
    ], $overrides));

    $order->items()->create([
        'article_name' => 'Testartikel',
        'quantity_ordered' => 2,
    ]);

    return $order;
}
