<?php

use App\Models\Order\Order;
use App\Models\Rental\Hall;
use App\Models\Rental\Rental;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Spatie\Activitylog\Models\Activity;

// ------------------------------------------------------------------ Zeitzone (M8)

test('the application runs in the german timezone', function () {
    expect(config('app.timezone'))->toBe('Europe/Berlin')
        ->and(date_default_timezone_get())->toBe('Europe/Berlin')
        ->and(now()->timezoneName)->toBe('Europe/Berlin');
});

test('the order number year follows the german new year', function () {
    $user = App\Models\User::factory()->create();
    $contact = makeContact();

    // 31.12. 23:30 UTC ist in Deutschland bereits der 1.1. um 00:30
    $this->travelTo(Carbon::parse('2026-12-31 23:30:00', 'UTC'));

    expect(makeOrder($contact, $user)->order_number)->toBe('ORD-2027-0001');
});

test('the order number year does not change before the german new year', function () {
    $user = App\Models\User::factory()->create();
    $contact = makeContact();

    // 31.12. 22:30 UTC ist in Deutschland noch der 31.12. um 23:30
    $this->travelTo(Carbon::parse('2026-12-31 22:30:00', 'UTC'));

    expect(makeOrder($contact, $user)->order_number)->toBe('ORD-2026-0001');
});

test('the dashboard shows rentals of the german day shortly after midnight', function () {
    $user = userWithPermissions(['rentals.view']);
    $hall = Hall::create(['name' => 'Dashboardhalle']);

    // 14.11. 23:30 UTC ist in Deutschland bereits der 15.11. um 00:30
    $this->travelTo(Carbon::parse('2026-11-14 23:30:00', 'UTC'));

    Rental::create([
        'contact_id' => makeContact()->id,
        'hall_id' => $hall->id,
        'start' => '2026-11-15 09:00:00',
        'end' => '2026-11-15 12:00:00',
        'status' => 'scheduled',
    ]);

    $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Dashboardhalle');
});

test('created timestamps are stored in german time', function () {
    $this->travelTo(Carbon::parse('2026-07-01 10:00:00', 'UTC'));

    $order = makeOrder(makeContact(), App\Models\User::factory()->create());

    // Im Sommer ist Deutschland UTC+2
    expect($order->refresh()->created_at->format('Y-m-d H:i'))->toBe('2026-07-01 12:00');
});

test('the timezone can be overridden with the environment variable', function () {
    $config = file_get_contents(config_path('app.php'));

    expect($config)->toContain("env('APP_TIMEZONE', 'Europe/Berlin')");
    expect(file_get_contents(base_path('.env.example')))->toContain('APP_TIMEZONE=Europe/Berlin');
});

// ------------------------------------------------- Aktivitaetslog bereinigen (M10)

test('the activity log cleanup is scheduled daily', function () {
    Artisan::call('schedule:list');

    expect(Artisan::output())->toContain('activitylog:clean');
});

test('the cleanup removes old activity entries and keeps recent ones', function () {
    activity('admin')->log('alter Eintrag');
    activity('admin')->log('neuer Eintrag');

    $old = Activity::where('description', 'alter Eintrag')->firstOrFail();
    $old->forceFill(['created_at' => now()->subDays(400)])->save();

    $this->artisan('activitylog:clean')->assertSuccessful();

    expect(Activity::where('description', 'alter Eintrag')->exists())->toBeFalse()
        ->and(Activity::where('description', 'neuer Eintrag')->exists())->toBeTrue();
});

test('the cleanup does not touch the order history', function () {
    $order = makeOrder(makeContact(), App\Models\User::factory()->create());
    $order->history()->update(['created_at' => now()->subDays(1000)]);

    $this->artisan('activitylog:clean')->assertSuccessful();

    expect($order->history()->count())->toBeGreaterThan(0)
        ->and(Order::count())->toBe(1);
});
