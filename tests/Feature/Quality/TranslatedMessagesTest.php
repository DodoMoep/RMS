<?php

use App\Enums\OrderStatus;
use App\Models\Order\Article;
use App\Models\Rental\Hall;
use App\Models\Rental\InventoryItem;
use App\Models\Rental\Protocol;
use App\Models\Rental\Rental;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Role;

/**
 * Meldungen der Controller muessen in der gewaehlten Sprache erscheinen.
 * Die Sprache kommt aus der Session (LanguageController, Middleware SetLocale).
 */
function firstError(): ?string
{
    return session('errors')?->first();
}

dataset('locales', [
    'german' => ['de', 'Halle kann nicht gelöscht werden, da noch Vermietungen vorhanden sind.'],
    'english' => ['en', 'The hall cannot be deleted because rentals still exist.'],
]);

test('the hall deletion error is shown in the selected language', function (string $locale, string $expected) {
    $user = userWithPermissions(['halls.view', 'halls.delete']);
    $hall = Hall::create(['name' => 'Sprachhalle']);
    Rental::create([
        'contact_id' => makeContact()->id,
        'hall_id' => $hall->id,
        'start' => now()->addDay(),
        'end' => now()->addDays(2),
        'status' => 'scheduled',
    ]);

    $this->withSession(['locale' => $locale])
        ->actingAs($user)
        ->delete(route('halls.destroy', $hall))
        ->assertSessionHasErrors();

    expect(firstError())->toBe($expected);
})->with('locales');

test('the success messages of the master data are translated', function (string $locale) {
    $user = userWithPermissions(['halls.view', 'halls.create', 'inventory.view', 'inventory.create', 'orders.create']);

    $this->withSession(['locale' => $locale])->actingAs($user);

    $this->post(route('halls.store'), ['name' => 'Meldungshalle'])
        ->assertSessionHas('ok', __('halls.messages.created', [], $locale));
    $this->post(route('inventory-items.store'), ['name' => 'Meldungsposition'])
        ->assertSessionHas('ok', __('inventory.messages.created', [], $locale));
    $this->post(route('articles.store'), ['name' => 'Meldungsartikel'])
        ->assertSessionHas('ok', __('articles.messages.created', [], $locale));
})->with(['de', 'en']);

test('the in use errors of inventory and articles are translated', function (string $locale) {
    $user = userWithPermissions(['inventory.view', 'inventory.delete', 'orders.create']);
    $item = InventoryItem::create(['name' => 'Verwendete Position']);
    Hall::create(['name' => 'Haus'])->inventory()->attach($item->id, ['quantity' => 1]);

    $this->withSession(['locale' => $locale])
        ->actingAs($user)
        ->delete(route('inventory-items.destroy', $item));

    expect(firstError())->toBe(__('inventory.messages.assigned_to_halls', [], $locale));

    $article = Article::create(['name' => 'Verwendeter Artikel']);
    makeOrder(makeContact(), $user)->items()->update(['article_id' => $article->id]);

    $this->delete(route('articles.destroy', $article));

    expect(firstError())->toBe(__('articles.messages.in_use', [], $locale));
})->with(['de', 'en']);

test('the hall booked error of a rental is translated', function (string $locale) {
    $user = userWithPermissions(['rentals.view', 'rentals.create']);
    $hall = Hall::create(['name' => 'Belegte Halle']);
    Rental::create([
        'contact_id' => makeContact()->id,
        'hall_id' => $hall->id,
        'start' => '2026-12-01 10:00',
        'end' => '2026-12-01 14:00',
        'status' => 'scheduled',
    ]);

    $this->withSession(['locale' => $locale])
        ->actingAs($user)
        ->post(route('rentals.store'), [
            'contact_id' => makeContact()->id,
            'hall_id' => $hall->id,
            'start' => '2026-12-01 11:00',
            'end' => '2026-12-01 12:00',
            'status' => 'scheduled',
        ])
        ->assertSessionHasErrors(['hall_id' => __('rentals.messages.hall_booked', [], $locale)]);
})->with(['de', 'en']);

test('the user administration messages are translated', function (string $locale) {
    $admin = userWithPermissions(['user.add', 'user.delete']);

    $this->withSession(['locale' => $locale])
        ->actingAs($admin)
        ->delete(route('users.destroy', $admin))
        ->assertForbidden();

    $this->post(route('users.store'), ['name' => 'Meldung', 'email' => "meldung-{$locale}@example.test", 'password' => 'geheim-1234'])
        ->assertSessionHas('status', __('users.messages.created', [], $locale));
})->with(['de', 'en']);

test('the role messages are translated', function (string $locale) {
    $admin = userWithPermissions(['role.add', 'role.edit', 'role.delete']);
    $super = Role::findOrCreate('super-admin', 'web');

    $this->withSession(['locale' => $locale])->actingAs($admin);

    $this->delete(route('roles.destroy', $super))
        ->assertSessionHas('error', __('roles.messages.protected', [], $locale));
})->with(['de', 'en']);

test('the delivery note and packing messages are translated', function (string $locale) {
    $user = userWithPermissions(['orders.print', 'orders.pack']);
    $order = makeOrder(makeContact(), $user, ['status' => OrderStatus::IN_PROGRESS]);

    $this->withSession(['locale' => $locale])
        ->actingAs($user)
        ->post(route('delivery-notes.generate', $order));

    expect(firstError())->toBe(__('delivery_notes.messages.only_packed', [], $locale));

    $this->get(route('delivery-notes.download', $order));

    expect(firstError())->toBe(__('delivery_notes.messages.none_available', [], $locale));
})->with(['de', 'en']);

test('the protocol messages are translated', function (string $locale) {
    $user = userWithPermissions(['protocols.view', 'protocols.edit']);
    $rental = Rental::create([
        'contact_id' => makeContact()->id,
        'hall_id' => Hall::create(['name' => 'Protokollhalle'])->id,
        'start' => now()->addDay(),
        'end' => now()->addDays(2),
        'status' => 'scheduled',
    ]);
    $protocol = Protocol::create(['rental_id' => $rental->id, 'type' => 'handover', 'checklist' => []]);

    $this->withSession(['locale' => $locale])
        ->actingAs($user)
        ->post(route('protocol.save', $protocol), ['notes' => 'Notiz'])
        ->assertSessionHas('ok', __('protocols.messages.saved', [], $locale));

    $protocol->update(['pdf_path' => 'protocols/fertig.pdf']);

    $this->post(route('protocol.save', $protocol), ['notes' => 'Noch eine Notiz']);

    expect(firstError())->toBe(__('protocols.messages.already_finalized', [], $locale));
})->with(['de', 'en']);

// ------------------------------------------------------------------ englische Mails

test('the password reset mail can be rendered in english', function () {
    App::setLocale('en');

    $html = (string) (new ResetPassword('test-token'))->toMail(User::factory()->make())->render();

    expect($html)->toContain('Hello!')
        ->and($html)->toContain('All rights reserved.');
});

test('the password reset mail can be rendered in german', function () {
    App::setLocale('de');

    $html = (string) (new ResetPassword('test-token'))->toMail(User::factory()->make())->render();

    expect(strlen($html))->toBeGreaterThan(100);
});
