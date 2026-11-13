<?php

use App\Models\Rental\Hall;
use App\Models\Rental\Rental;
use Illuminate\Support\Carbon;

function overlapPayload(Hall $hall, string $start, string $end, array $overrides = []): array
{
    return array_merge([
        'contact_id' => makeContact(['type' => App\Enums\ContactType::Tenant])->id,
        'hall_id' => $hall->id,
        'start' => $start,
        'end' => $end,
        'status' => 'scheduled',
    ], $overrides);
}

function bookedRental(Hall $hall, string $start, string $end, string $status = 'scheduled'): Rental
{
    return Rental::create([
        'contact_id' => makeContact(['type' => App\Enums\ContactType::Tenant])->id,
        'hall_id' => $hall->id,
        'start' => $start,
        'end' => $end,
        'status' => $status,
    ]);
}

beforeEach(function () {
    $this->hall = Hall::create(['name' => 'Testhalle']);
    $this->user = userWithPermissions(['rentals.view', 'rentals.create', 'rentals.edit']);
    bookedRental($this->hall, '2026-11-10 10:00', '2026-11-10 14:00');
});

test('a rental in a free period is created', function () {
    $this->actingAs($this->user)
        ->post(route('rentals.store'), overlapPayload($this->hall, '2026-11-12 10:00', '2026-11-12 14:00'))
        ->assertRedirect(route('rentals.index'));

    expect(Rental::count())->toBe(2);
});

test('a rental that starts exactly when another one ends is allowed', function () {
    $this->actingAs($this->user)
        ->post(route('rentals.store'), overlapPayload($this->hall, '2026-11-10 14:00', '2026-11-10 18:00'))
        ->assertRedirect(route('rentals.index'))
        ->assertSessionHasNoErrors();

    expect(Rental::count())->toBe(2);
});

test('a rental that ends exactly when another one starts is allowed', function () {
    $this->actingAs($this->user)
        ->post(route('rentals.store'), overlapPayload($this->hall, '2026-11-10 06:00', '2026-11-10 10:00'))
        ->assertRedirect(route('rentals.index'))
        ->assertSessionHasNoErrors();

    expect(Rental::count())->toBe(2);
});

test('overlapping rentals are rejected', function (string $start, string $end) {
    $this->actingAs($this->user)
        ->post(route('rentals.store'), overlapPayload($this->hall, $start, $end))
        ->assertSessionHasErrors('hall_id');

    expect(Rental::count())->toBe(1);
})->with([
    'starts inside' => ['2026-11-10 12:00', '2026-11-10 16:00'],
    'ends inside' => ['2026-11-10 08:00', '2026-11-10 11:00'],
    'lies inside' => ['2026-11-10 11:00', '2026-11-10 13:00'],
    'contains it' => ['2026-11-10 08:00', '2026-11-10 16:00'],
    'identical period' => ['2026-11-10 10:00', '2026-11-10 14:00'],
    'one minute overlap at the end' => ['2026-11-10 13:59', '2026-11-10 18:00'],
    'one minute overlap at the start' => ['2026-11-10 06:00', '2026-11-10 10:01'],
]);

test('the same period in another hall is allowed', function () {
    $other = Hall::create(['name' => 'Andere Halle']);

    $this->actingAs($this->user)
        ->post(route('rentals.store'), overlapPayload($other, '2026-11-10 10:00', '2026-11-10 14:00'))
        ->assertRedirect(route('rentals.index'));

    expect(Rental::count())->toBe(2);
});

test('a cancelled rental does not block the hall', function () {
    Rental::query()->update(['status' => 'cancelled']);

    $this->actingAs($this->user)
        ->post(route('rentals.store'), overlapPayload($this->hall, '2026-11-10 10:00', '2026-11-10 14:00'))
        ->assertRedirect(route('rentals.index'));

    expect(Rental::count())->toBe(2);
});

test('a rental can be changed without colliding with itself', function () {
    $rental = Rental::firstOrFail();

    $this->actingAs($this->user)
        ->put(route('rentals.update', $rental), overlapPayload($this->hall, '2026-11-10 11:00', '2026-11-10 15:00', [
            'contact_id' => $rental->contact_id,
        ]))
        ->assertRedirect(route('rentals.index'));

    expect($rental->refresh()->start)->toEqual(Carbon::parse('2026-11-10 11:00'));
});

test('a rental cannot be moved into another booking when updating', function () {
    $other = bookedRental($this->hall, '2026-11-11 10:00', '2026-11-11 14:00');

    $this->actingAs($this->user)
        ->put(route('rentals.update', $other), overlapPayload($this->hall, '2026-11-10 13:00', '2026-11-10 17:00', [
            'contact_id' => $other->contact_id,
        ]))
        ->assertSessionHasErrors('hall_id');

    expect($other->refresh()->start)->toEqual(Carbon::parse('2026-11-11 10:00'));
});

test('a rental can be moved to start exactly when another one ends when updating', function () {
    $other = bookedRental($this->hall, '2026-11-11 10:00', '2026-11-11 14:00');

    $this->actingAs($this->user)
        ->put(route('rentals.update', $other), overlapPayload($this->hall, '2026-11-10 14:00', '2026-11-10 17:00', [
            'contact_id' => $other->contact_id,
        ]))
        ->assertRedirect(route('rentals.index'));

    expect($other->refresh()->start)->toEqual(Carbon::parse('2026-11-10 14:00'));
});
