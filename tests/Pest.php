<?php

use App\Enums\ContactType;
use App\Enums\OrderStatus;
use App\Models\Contact;
use App\Models\Order\Article;
use App\Models\Order\Order;
use App\Models\Rental\Hall;
use App\Models\Rental\Protocol;
use App\Models\Rental\Rental;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

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

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
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
function userWithPermissions(array $permissions): User
{
    $user = User::factory()->create();

    foreach ($permissions as $name) {
        Permission::findOrCreate($name, 'web');
    }

    $user->givePermissionTo($permissions);

    return $user;
}

/**
 * Legt einen Kunden mit Lieferadresse an (erfundene Testdaten).
 *
 * @param  array<string, mixed>  $overrides
 */
function makeContact(array $overrides = []): Contact
{
    return Contact::create(array_merge([
        'type' => ContactType::Customer,
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
function makeOrder(Contact $contact, User $creator, array $overrides = []): Order
{
    $order = Order::create(array_merge([
        'contact_id' => $contact->id,
        'delivery_date' => now()->addDays(3)->toDateString(),
        'delivery_type' => 'delivery',
        'status' => OrderStatus::IN_PROGRESS,
        'created_by' => $creator->id,
    ], $overrides));

    $order->items()->create([
        'article_name' => 'Testartikel',
        'quantity_ordered' => 2,
    ]);

    return $order;
}

/**
 * Legt einen aktiven Artikel an (erfundene Testdaten).
 *
 * @param  array<string, mixed>  $overrides
 */
function makeArticle(array $overrides = []): Article
{
    return Article::create(array_merge([
        'name' => 'Testartikel',
        'sku' => 'ART-'.fake()->unique()->numerify('####'),
        'price' => 12.50,
        'is_active' => true,
    ], $overrides));
}

/**
 * Bildet die MariaDB-Funktion DATE_FORMAT unter SQLite nach, damit die Auswertungsabfragen
 * (Order::ordersOverTime) in der Testumgebung laufen. Unterstuetzt werden die Formate der
 * Anwendung: %Y, %m, %d, %H und %u (Kalenderwoche). Die echte MariaDB-Funktion prueft das
 * nicht, die Abfrage selbst ist daher nur auf MariaDB voll abgesichert.
 */
function registerSqliteDateFormat(): void
{
    DB::connection()->getPdo()->sqliteCreateFunction('DATE_FORMAT', function ($value, $format) {
        if ($value === null) {
            return null;
        }

        return Carbon::parse($value)->format(strtr($format, [
            '%Y' => 'Y', '%m' => 'm', '%d' => 'd', '%H' => 'H', '%u' => 'W',
        ]));
    }, 2);
}

/**
 * Legt ein Protokoll mit Vermietung, Halle, Mieter (englische Namen, damit Sprachtests
 * deutsche Texte sicher erkennen) und einer Position an.
 */
function makeRentalProtocol(string $type = 'handover', string $rentalStatus = 'scheduled'): Protocol
{
    $tenant = makeContact([
        'type' => ContactType::Tenant,
        'name' => 'Test Club',
        'email' => 'club@example.test',
    ]);
    $hall = Hall::create(['name' => 'Test Hall', 'address' => 'Test Street 1']);
    $rental = Rental::create([
        'contact_id' => $tenant->id,
        'hall_id' => $hall->id,
        'start' => now()->addDay(),
        'end' => now()->addDays(2),
        'status' => $rentalStatus,
    ]);
    $protocol = Protocol::create([
        'rental_id' => $rental->id,
        'type' => $type,
        'checklist' => ['stromzaehler' => null, 'wasserzaehler' => null],
    ]);
    $protocol->items()->create(['label' => 'Test Chair (Soll: 4)', 'state' => 'ok']);

    return $protocol;
}

/**
 * Gueltige Unterschrift: ein PNG mit 1 mal 1 Pixel als Data-URI.
 */
function signaturePngDataUri(): string
{
    return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
}
