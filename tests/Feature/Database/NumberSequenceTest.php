<?php

use App\Models\Contact;
use App\Models\User;
use App\Services\NumberSequence;
use Illuminate\Support\Facades\DB;

function orderNumberYear(): string
{
    return now()->format('Y');
}

test('order numbers are consecutive when orders are created within the same second', function () {
    $user = User::factory()->create();
    $contact = makeContact();
    $year = orderNumberYear();

    $numbers = collect(range(1, 5))->map(fn () => makeOrder($contact, $user)->order_number);

    expect($numbers->all())->toBe(collect(range(1, 5))->map(fn ($i) => sprintf('ORD-%s-%04d', $year, $i))->all());
});

test('the order counter is stored in the sequence table', function () {
    $user = User::factory()->create();
    $contact = makeContact();
    makeOrder($contact, $user);
    makeOrder($contact, $user);
    makeOrder($contact, $user);

    expect((int) DB::table('number_sequences')->where('name', 'order-'.orderNumberYear())->value('last_value'))->toBe(3);
});

test('an order number is not reused after the order was deleted', function () {
    $user = User::factory()->create();
    $contact = makeContact();
    $year = orderNumberYear();

    makeOrder($contact, $user);
    makeOrder($contact, $user)->delete();

    expect(makeOrder($contact, $user)->order_number)->toBe("ORD-{$year}-0003");
});

test('the order counter continues after existing order numbers', function () {
    $user = User::factory()->create();
    $contact = makeContact();
    $year = orderNumberYear();

    makeOrder($contact, $user, ['order_number' => "ORD-{$year}-0041"]);

    expect(makeOrder($contact, $user)->order_number)->toBe("ORD-{$year}-0042");
});

test('the order counter also considers deleted orders when it starts', function () {
    $user = User::factory()->create();
    $contact = makeContact();
    $year = orderNumberYear();

    makeOrder($contact, $user, ['order_number' => "ORD-{$year}-0007"])->delete();

    expect(makeOrder($contact, $user)->order_number)->toBe("ORD-{$year}-0008");
});

test('order numbers keep counting correctly beyond 9999', function () {
    $user = User::factory()->create();
    $contact = makeContact();
    $year = orderNumberYear();

    makeOrder($contact, $user, ['order_number' => "ORD-{$year}-9999"]);
    makeOrder($contact, $user, ['order_number' => "ORD-{$year}-10000"]);

    expect(makeOrder($contact, $user)->order_number)->toBe("ORD-{$year}-10001");
});

test('order numbers restart with the new year', function () {
    $user = User::factory()->create();
    $contact = makeContact();
    makeOrder($contact, $user);

    $this->travelTo(now()->setDate(now()->year + 1, 1, 2));

    expect(makeOrder($contact, $user)->order_number)->toBe(sprintf('ORD-%s-0001', now()->format('Y')));
});

test('customer numbers are consecutive and continue after existing numbers', function () {
    $year = orderNumberYear();

    makeContact(['customer_number' => "CUS-{$year}-0007"]);

    $numbers = collect(range(1, 3))->map(fn ($i) => makeContact(['name' => "Serienkunde {$i}"])->customer_number);

    expect($numbers->all())->toBe(["CUS-{$year}-0008", "CUS-{$year}-0009", "CUS-{$year}-0010"])
        ->and(Contact::where('customer_number', "CUS-{$year}-0008")->count())->toBe(1);
});

test('the sequence is only seeded once', function () {
    $calls = 0;
    $seed = function () use (&$calls) {
        $calls++;

        return 10;
    };

    expect(NumberSequence::next('test-seq', $seed))->toBe(11)
        ->and(NumberSequence::next('test-seq', $seed))->toBe(12)
        ->and($calls)->toBe(1);
});

test('the highest suffix helper rejects unsafe table and column names', function () {
    NumberSequence::highestSuffix('orders; drop table orders', 'order_number', 'ORD-');
})->throws(InvalidArgumentException::class);
