<?php

use App\Models\Rental\Hall;
use App\Models\Rental\InventoryItem;
use App\Models\Rental\Rental;

/**
 * Die Seiten der Vermietung haben keine festen deutschen Texte mehr. Gerendert wird mit
 * englischen Daten (Namen) in der Sprache "en"; deutsche Woerter duerfen dann nirgends vorkommen.
 */
const GERMAN_MARKERS = [
    'Speichern', 'Abbrechen', 'Bearbeiten', 'Löschen', 'Aktionen', 'Mieter', 'Vermieter',
    'Keine Einträge', 'Übergabe', 'Rücknahme', 'Notizen', 'Beschreibung', 'Bezeichnung',
    'Adresse', 'Halle', 'Vermietung', 'Unterschrift', 'Zeitraum', 'Stromzähler', 'Wasserzähler',
    'Fotos', 'Kommentar', 'Gebühr', 'Fehlt', 'Beschädigt', 'Suchen', 'Zurücksetzen', 'Position',
    'Kaution', 'Preis', 'Inventar', 'Finalisiert', 'finalisiert', 'Erstellt', 'öffnen', 'vorhanden',
];

function rentalUser()
{
    return userWithPermissions([
        'halls.view', 'halls.create', 'halls.edit', 'halls.delete',
        'inventory.view', 'inventory.create', 'inventory.edit', 'inventory.delete',
        'rentals.view', 'rentals.create', 'rentals.edit', 'rentals.delete', 'rentals.handover', 'rentals.return',
        'protocols.view', 'protocols.create', 'protocols.edit', 'protocols.sign', 'protocols.pdf',
    ]);
}

/**
 * Alle Seiten mit Daten, damit auch Tabellenzeilen, Auswahllisten und Schaltflaechen erscheinen.
 *
 * @return array<string, string>
 */
function rentalPageUrls(): array
{
    $protocol = makeRentalProtocol();
    $rental = $protocol->rental;
    $hall = $rental->hall;
    $item = InventoryItem::create(['name' => 'Test Chair', 'sku' => 'TC-1', 'description' => 'A chair']);
    $hall->inventory()->sync([$item->id => ['quantity' => 4]]);

    $finalized = makeRentalProtocol('return', 'active');
    $finalized->update(['pdf_path' => 'protocols/done.pdf']);

    return [
        'halls index' => route('halls.index'),
        'halls create' => route('halls.create'),
        'halls edit' => route('halls.edit', $hall),
        'inventory index' => route('inventory-items.index'),
        'inventory create' => route('inventory-items.create'),
        'inventory edit' => route('inventory-items.edit', $item),
        'rentals index' => route('rentals.index'),
        'rentals create' => route('rentals.create'),
        'rentals edit' => route('rentals.edit', $rental),
        'protocols index' => route('protocols.index'),
        'protocol form open' => route('protocol.form', $protocol),
        'protocol form finalized' => route('protocol.form', $finalized),
    ];
}

test('the rental pages contain no german text in english', function () {
    $urls = rentalPageUrls();
    $failures = [];

    foreach ($urls as $name => $url) {
        $html = $this->actingAs(rentalUser())
            ->withSession(['locale' => 'en'])
            ->get($url)
            ->assertOk()
            ->getContent();

        // Nur sichtbarer Text, keine Skripte und keine Attribute wie Pfade
        $text = strip_tags(preg_replace('#<script\b.*?</script>#si', '', $html));

        foreach (GERMAN_MARKERS as $word) {
            if (str_contains($text, $word)) {
                $failures[] = "{$name}: {$word}";
            }
        }
    }

    expect($failures)->toBe([]);
});

test('the rental pages are german in german', function () {
    $urls = rentalPageUrls();

    $expected = [
        'halls index' => ['Hallen', 'Adresse', 'Bearbeiten', 'Löschen'],
        'halls create' => ['Neue Halle', 'Inventar der Halle', 'Speichern', 'Abbrechen'],
        'halls edit' => ['Halle bearbeiten', 'Notizen'],
        'inventory index' => ['Inventar', 'Bezeichnung', 'Beschreibung'],
        'inventory create' => ['Neue Inventar-Position', 'Speichern'],
        'inventory edit' => ['Inventar-Position bearbeiten'],
        'rentals index' => ['Mieter:in', 'Kaution', 'Übergabe öffnen', 'Rücknahme anlegen', 'geplant'],
        'rentals create' => ['Neue Vermietung', 'Preis (€)', 'Kaution (€)', 'geplant', 'storniert'],
        'rentals edit' => ['Vermietung bearbeiten'],
        'protocols index' => ['Typ', 'Erstellt', 'öffnen', 'vorhanden'],
        'protocol form open' => ['Übergabe', 'Stromzähler', 'Wasserzähler', 'Beschädigt', 'Unterschreiben &amp; Speichern'],
        'protocol form finalized' => ['Rücknahme', 'Dieses Protokoll wurde finalisiert', 'keine weitere Signatur möglich'],
    ];

    foreach ($urls as $name => $url) {
        $response = $this->actingAs(rentalUser())->withSession(['locale' => 'de'])->get($url)->assertOk();

        foreach ($expected[$name] as $text) {
            $response->assertSee($text, false);
        }
    }
});

test('the dashboard of the rental area is translated', function () {
    $protocol = makeRentalProtocol();
    // Die Vermietung muss heute liegen, damit sie in der Tabelle erscheint
    $protocol->rental->update(['start' => now()->subHour(), 'end' => now()->addHour()]);
    $user = rentalUser();

    $english = $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('dashboard'))->assertOk();
    $text = strip_tags(preg_replace('#<script\b.*?</script>#si', '', $english->getContent()));

    $english->assertSee('Today – Rentals')->assertSee('Protocols without PDF (open)')->assertSee('scheduled');

    foreach (GERMAN_MARKERS as $word) {
        expect($text)->not->toContain($word);
    }

    $this->actingAs($user)->withSession(['locale' => 'de'])->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Heute – Vermietungen')
        ->assertSee('Protokolle ohne PDF (offen)')
        ->assertSee('Übergabe')
        ->assertSee('Rücknahme')
        ->assertSee('geplant');
});

test('the dashboard welcome text is translated for users without rental rights', function () {
    $user = userWithPermissions([]);

    $this->actingAs($user)->withSession(['locale' => 'en'])->get(route('dashboard'))
        ->assertSee('Welcome to the Rosenau Management System')
        ->assertDontSee('Willkommen');

    $this->actingAs($user)->withSession(['locale' => 'de'])->get(route('dashboard'))
        ->assertSee('Willkommen im Rosenau Management System');
});

test('the halls are called hall and not room in german', function () {
    Hall::create(['name' => 'Test Hall']);

    $this->actingAs(rentalUser())
        ->withSession(['locale' => 'de'])
        ->get(route('halls.index'))
        ->assertOk()
        ->assertSee('Hallen')
        ->assertDontSee('Säle')
        ->assertDontSee('Saal');
});

test('the rental status is shown as a translated label', function () {
    $rental = makeRentalProtocol()->rental;

    $german = $this->actingAs(rentalUser())->withSession(['locale' => 'de'])->get(route('rentals.index'));
    $english = $this->actingAs(rentalUser())->withSession(['locale' => 'en'])->get(route('rentals.index'));

    expect($rental->status)->toBe('scheduled');
    $german->assertSee('geplant');
    $english->assertSee('scheduled')->assertDontSee('geplant');
});

test('every rental status has a label in both languages', function (string $status) {
    expect(__("rentals.statuses.{$status}", [], 'de'))->not->toBe("rentals.statuses.{$status}")
        ->and(__("rentals.statuses.{$status}", [], 'en'))->not->toBe("rentals.statuses.{$status}");
})->with(['scheduled', 'active', 'closed', 'cancelled']);

test('the rental list still counts the rows of the table', function () {
    $protocol = makeRentalProtocol();
    Rental::create([
        'contact_id' => $protocol->rental->contact_id,
        'hall_id' => $protocol->rental->hall_id,
        'start' => now()->addDays(10),
        'end' => now()->addDays(11),
        'status' => 'active',
    ]);

    $response = $this->actingAs(rentalUser())->get(route('rentals.index'))->assertOk();

    expect($response->viewData('rentals'))->toHaveCount(2);
    $response->assertSee('aktiv');
});
