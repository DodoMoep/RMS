<?php

use App\Enums\OrderStatus;
use App\Models\Order\Order;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;

/**
 * Faengt die Daten ab, die der Export an die PDF-Vorlage uebergibt.
 */
function captureExportData(?array &$captured): void
{
    Pdf::shouldReceive('loadView')
        ->once()
        ->andReturnUsing(function (string $view, array $data) use (&$captured) {
            $captured = $data;

            $pdf = Mockery::mock(PdfDocument::class);
            $pdf->shouldReceive('download')->andReturn(response('pdf', 200, ['Content-Type' => 'application/pdf']));

            return $pdf;
        });
}

function ordersAt(User $user, array $daysAgo): void
{
    $contact = makeContact();

    foreach ($daysAgo as $days) {
        $order = makeOrder($contact, $user, ['status' => OrderStatus::NEW]);
        $order->forceFill(['created_at' => now()->subDays($days)])->saveQuietly();
    }
}

test('the export only contains orders of the selected period', function () {
    $user = userWithPermissions(['analytics.view', 'analytics.export']);
    ordersAt($user, [1, 3, 20, 100, 400]);

    captureExportData($captured);
    $this->actingAs($user)->get(route('analytics.export', ['period' => 7]))->assertOk();

    expect($captured['totalOrders'])->toBe(2)
        ->and($captured['statusData']->sum('count'))->toBe(2);
});

test('the export for a longer period contains more orders', function () {
    $user = userWithPermissions(['analytics.view', 'analytics.export']);
    ordersAt($user, [1, 3, 20, 100, 400]);

    captureExportData($captured);
    $this->actingAs($user)->get(route('analytics.export', ['period' => 90]))->assertOk();

    expect($captured['totalOrders'])->toBe(3);
});

test('the export for all time contains every order', function () {
    $user = userWithPermissions(['analytics.view', 'analytics.export']);
    ordersAt($user, [1, 3, 20, 100, 400]);

    captureExportData($captured);
    $this->actingAs($user)->get(route('analytics.export', ['period' => 'all']))->assertOk();

    expect($captured['totalOrders'])->toBe(5)
        ->and(Order::count())->toBe(5);
});

test('the export defaults to 30 days', function () {
    $user = userWithPermissions(['analytics.view', 'analytics.export']);
    ordersAt($user, [1, 20, 100]);

    captureExportData($captured);
    $this->actingAs($user)->get(route('analytics.export'))->assertOk();

    expect($captured['totalOrders'])->toBe(2);
});

test('the export states the period in the report', function () {
    $user = userWithPermissions(['analytics.view', 'analytics.export']);

    captureExportData($captured);
    $this->actingAs($user)->get(route('analytics.export', ['period' => 7]))->assertOk();

    $html = view('order.analytics.report-pdf', $captured)->render();

    expect($html)->toContain('Zeitraum: Letzte 7 Tage');

    captureExportData($capturedAll);
    $this->actingAs($user)->get(route('analytics.export', ['period' => 'all']))->assertOk();

    expect(view('order.analytics.report-pdf', $capturedAll)->render())->toContain('Alle Zeit');
});

test('the export rejects invalid periods', function () {
    $user = userWithPermissions(['analytics.view', 'analytics.export']);

    $this->actingAs($user)
        ->get(route('analytics.export', ['period' => 'abc']))
        ->assertSessionHasErrors('period');
    $this->actingAs($user)
        ->get(route('analytics.export', ['period' => 9999]))
        ->assertSessionHasErrors('period');
});

test('the export requires the export permission', function () {
    $this->actingAs(userWithPermissions(['analytics.view']))
        ->get(route('analytics.export'))
        ->assertForbidden();
});
