# 08 Bekannte Probleme und Risiken

Stand: Commit `6787310`. Jeder Befund nennt Fundstelle, Auswirkung, Nachweis und einen Korrekturvorschlag. K1 bis K7, S1 bis S6, M1 bis M11, T1, T2, T3, T5 und T6 sind behoben, T4 teilweise; offen sind die beim Schreiben der T1-Tests gefundenen Punkte M12 bis M15.

Nachweis:

- **Skript** = per PHP-Skript gegen die gebootete Anwendung bestätigt (ohne Datenbankzugriff)
- **Code** = aus dem Quelltext abgeleitet, nicht ausgeführt

Kategorien: **K** Funktionsfehler, **S** Sicherheit und Datenschutz, **M** Fachliche oder technische Mängel, **T** Technische Schulden.

## Übersicht nach Priorität

| ID | Priorität | Kurzbeschreibung | Nachweis |
|---|---|---|---|
| K1 | Behoben | `Contact::orders()` und `rentals()` zeigen auf nicht existierende Klassen | Skript |
| K2 | Behoben | Packübersicht lädt entfernte Relation `customer` | Skript |
| K3 | Behoben | Lieferschein-Template liest `$order->customer` | Skript |
| K4 | Behoben | Inventar bearbeiten, ändern, löschen arbeitet auf leerem Model | Skript |
| K5 | Behoben | Protokolle nutzen entfernte Relation `tenant`, Finalisierung schlägt fehl | Skript und Code |
| K6 | Behoben | Auftrag löschen schreibt Historie nach dem Löschen | Code |
| K7 | Behoben | Nummernvergabe nicht atomar | Code |
| S1 | Behoben | PDFs, Unterschriften, Fotos öffentlich abrufbar | Code |
| S2 | Behoben | Fehlermeldungen aus Ausnahmen werden Benutzern angezeigt | Code |
| S3 | Behoben | Benutzer löschen entfernt per Kaskade deren Aufträge | Code |
| S4 | Behoben | Lokale Werkzeugeinstellungen im Repository | Code |
| S5 | Behoben | Externes Skript ohne Integritätsprüfung | Code |
| S6 | Behoben | Abhängigkeiten mit bekannten Schwachstellen (55 Hinweise in 15 Composer-Paketen, 12 in npm) | Skript (`composer audit`, `npm audit`) |
| M1 | Behoben | Packen ohne Statusprüfung des Auftrags | Code |
| M2 | Behoben | Lieferschein-Druck umgeht die Statusübergänge | Code |
| M3 | Behoben | Alpine.js wird verwendet, aber nicht geladen | Code |
| M4 | Behoben | PDF-Export ignoriert den Zeitraum | Code |
| M5 | Behoben | Im Admin angelegte Benutzer sind nicht verifiziert | Code |
| M6 | Behoben | Seeder und Rechte nach Kontaktumbau veraltet | Code |
| M7 | Behoben | Meldungen mit `data-bs-dismiss` schließen nicht | Skript (CoreUI-Quelltext) |
| M8 | Behoben | Zeitzone UTC, Jahreswechsel und Anzeige | Code |
| M9 | Behoben | Aneinandergrenzende Vermietungen gelten als Überschneidung | Code |
| M10 | Behoben | Aktivitätslog ohne Bereinigung | Code |
| M11 | Behoben | Englische Mail-Sprachdatei mit Syntaxfehler | Skript (`php -l`) |
| M12 | Mittel | Statuswechsel von Hand umgeht Packer- und Druck-Nachweise | Skript (Probe-Test) |
| M13 | Niedrig | Artikelnummer eines gelöschten Artikels bleibt gesperrt | Skript (Probe-Test) |
| M14 | Niedrig | Auftrag lässt sich per Anfrage für reine Mieter anlegen | Skript (Probe-Test) |
| M15 | Niedrig | Rolle oder Recht anlegen verlangt zusätzlich das Bearbeiten-Recht | Skript (Probe-Test) |
| T1 | Behoben | Tests für Fachmodule nur teilweise vorhanden (Aufträge, Vermietungen, Auswertung fehlen) | Code |
| T2 | Behoben | Stilabweichungen in 101 von 292 PHP-Dateien (Pint) | Skript (Pint) |
| T3 | Behoben | Toter Code, ungenutzte Pakete (Rest: auskommentierter Menüpunkt) | Code |
| T4 | Teilweise behoben | Feste deutsche Texte: Controller übersetzt, Views der Vermietung offen | Code |
| T5 | Behoben | Zwei UUID-Mechanismen, Alias-Aufrufe, doppelter Code | Code |
| T6 | Mittel | Lokales `vendor/` unvollständig, Tests nicht lauffähig | Skript |

## K Funktionsfehler (Folgen des Kontaktumbaus)

Ursache K1 bis K5 (K6 und K7 haben eigene Ursachen): Die Umstellung von `Tenant` und `Customer` auf `Contact` und die Verschiebung der Models in `App\Models\Order` und `App\Models\Rental` wurde nicht überall nachgezogen. PHP meldet fehlende Importe und entfernte Relationen erst zur Laufzeit.

### K1 Contact-Relationen defekt

- Status: behoben (Imports in `Contact.php`). Tests: `tests/Feature/Contacts/ContactTest.php`.
- Fundstelle: `app/Models/Contact.php`, Methoden `orders()` und `rentals()`
- Ursache: `Order::class` und `Rental::class` lösen im Namespace `App\Models` zu `App\Models\Order` und `App\Models\Rental` auf; beide existieren nicht als Klasse (die Models liegen unter `Order\` und `Rental\`). Es fehlen die `use`-Anweisungen.
- Nachweis: Skript, `(new Contact)->orders()` wirft `Class "App\Models\Order" not found`; `class_exists` liefert für beide Namen `false`.
- Auswirkung: Kontaktliste (`withCount`), Kontaktdetail (`load`, `loadCount`) und Löschen (`orders()->exists()`) schlagen fehl, damit ist `/contacts` praktisch nicht nutzbar.
- Korrektur: `use App\Models\Order\Order;` und `use App\Models\Rental\Rental;` in `Contact.php` ergänzen; ungenutzte Imports `BelongsTo` und gleichnamige Doppelungen prüfen.

### K2 Packübersicht

- Status: behoben (`contact` statt `customer` im Controller und in der View). Tests: `tests/Feature/Orders/PackingTest.php`.
- Fundstelle: `OrderPackingController::index`, `->with(['creator', 'customer'])`; View `order/packing/index.blade.php` zeigt `$order->customer->name`
- Nachweis: Skript, `Order::customer()` existiert nicht (`BadMethodCallException`)
- Auswirkung: Die Packübersicht `/packing` liefert einen Fehler, Packer können nicht arbeiten.
- Korrektur: `customer` durch `contact` ersetzen, in der View `$order->contact->name`.

### K3 Lieferschein

- Status: behoben (`$order->contact` im Template). Tests: `tests/Feature/Orders/DeliveryNoteTest.php`.
- Fundstelle: `resources/views/order/delivery-notes/template.blade.php` (Empfängerblock, Zeilen um 170 bis 181)
- Auswirkung: `$order->customer` ist `null`, der Zugriff `->name` löst eine Fehlermeldung aus; Lieferscheine können nicht erzeugt werden (`generate`, `print`).
- Korrektur: `$order->contact->...` verwenden (Feldnamen sind identisch: `name`, `street`, `zip_code`, `city`, `address_notes`, `phone`).

### K4 Inventarpositionen: Route-Model-Binding

- Status: behoben (Parameter in `$inventoryItem` umbenannt). Tests: `tests/Feature/Rental/InventoryItemTest.php`.
- Fundstelle: `routes/rental.php` (`{inventoryItem}`) und `InventoryItemController` (`$inventory_item` in `edit`, `update`, `destroy`)
- Nachweis: Skript, nach `ImplicitRouteBinding::resolveForRoute` bleibt der Parameter ein String, und `edit()` erhält eine neue, nicht gespeicherte `InventoryItem`-Instanz (`exists = false`).
- Auswirkung: Das Bearbeitungsformular ist leer, `update` ändert nichts, `destroy` prüft und löscht keinen echten Datensatz. Die Verwaltung bestehender Inventarpositionen funktioniert nicht.
- Korrektur: Controller-Parameter in `$inventoryItem` umbenennen (und `['item' => $inventoryItem]` bzw. die View-Variable anpassen).

### K5 Protokolle und Mieter

- Status: behoben (`contact` in `ProtocolController` und drei Views, Mailempfänger aus `contact`). Tests: `tests/Feature/Rental/ProtocolTest.php`, darunter die komplette Finalisierung mit PDF, Statuswechsel und Mail.
- Fundstelle: `ProtocolController` (`rental.tenant` in `index`, `showForm`, `sign`; `->tenant?->email`), Views `rental/protocol/index`, `rental/protocol/form`, `pdf/protocol`
- Nachweis: Skript, `Rental::tenant()` existiert nicht
- Auswirkung: Protokollliste und Formular schlagen fehl. In `sign()` wirft `load(['rental.tenant', ...])` bei der zweiten Unterschrift (Auslöser der Finalisierung) einen Fehler, der von der Transaktion aufgefangen wird: Protokolle lassen sich nicht abschließen, es entstehen weder PDF noch E-Mail. Selbst bei erfolgreichem Laden würde der Mailversand wegen `tenant?->email` still entfallen.
- Korrektur: Überall `tenant` durch `contact` ersetzen (Eager-Loading-Strings, Views, Mailempfänger).

### K6 Auftrag löschen und Historie

- Fundstelle: `OrderObserver::deleted` ruft `logHistory` auf; `order_history.order_id` hat `cascade` auf `orders`
- Auswirkung: Nach dem Löschen des Auftrags wird versucht, eine Historienzeile für einen nicht mehr vorhandenen Auftrag einzufügen; der Fremdschlüssel verletzt die Integrität. Erwartetes Ergebnis: Der Auftrag ist gelöscht, der Benutzer sieht aber einen Fehler (keine umschließende Transaktion). Zudem entfällt die gesamte Historie des Auftrags, eine Nachvollziehbarkeit des Löschens besteht nicht.
- Nachweis: Der Fehler wurde per Test reproduziert (`FOREIGN KEY constraint failed` beim Einfügen des Ereignisses `order_deleted`).
- Status: behoben. `Order` verwendet `SoftDeletes` (Migration `2026_10_23_000001_add_soft_deletes_to_orders_table`). Historie und Positionen bleiben erhalten, das Ereignis `order_deleted` wird geschrieben, die Auftragsnummer bleibt belegt. `Order::topItems()` filtert jetzt immer über den Auftrag, damit Positionen gelöschter Aufträge nicht zählen; Packen und Entpacken von Positionen gelöschter Aufträge liefern 404. Tests: `tests/Feature/Orders/OrderDeletionTest.php`.
- Hinweis: Wird ein Kontakt gelöscht, der nur noch weich gelöschte Aufträge hat, setzt der Fremdschlüssel (`nullOnDelete`) deren `contact_id` auf `null`.

### K7 Nummernvergabe

- Fundstelle: `Order::generateOrderNumber`, `Contact::generateCustomerNumber`
- Auswirkung: Gleichzeitiges Anlegen erzeugt doppelte Nummern; die Unique-Constraint führt zu einem Fehler. Wird der zuletzt angelegte Auftrag gelöscht, wird seine Nummer erneut vergeben.
- Status: behoben. Neuer Dienst `App\Services\NumberSequence` mit Zählertabelle `number_sequences` (Migration `2026_10_23_000002`): Hochzählen in einer Transaktion mit `lockForUpdate`; der Zähler wird beim ersten Zugriff aus der höchsten vorhandenen Nummer abgeleitet (auch bei mehr als 9999 Nummern und unter Einschluss gelöschter Aufträge). `Order::generateOrderNumber` und `Contact::generateCustomerNumber` nutzen ihn. Gelöschte Nummern werden nicht erneut vergeben. Tests: `tests/Feature/Database/NumberSequenceTest.php`, `ContactTest`.
- Einschränkung: Die echte Gleichzeitigkeit lässt sich in der Testumgebung (SQLite, ein Prozess) nicht nachstellen; die Sperre wirkt auf MariaDB. Vor dem Produktivbetrieb ist ein Test mit parallelen Anfragen auf MariaDB sinnvoll.
- Hinweis: Fehlschlagende Anlegevorgänge können Lücken in der Nummernfolge hinterlassen. Der Jahreswechsel richtet sich nach der Zeitzone `Europe/Berlin` (M8).

## S Sicherheit und Datenschutz

### S1 Öffentlich abgelegte Dateien

- Fundstelle: Alle Dateien liegen auf der Disk `public`; das Verzeichnis `public/storage` ist in der lokalen Umgebung vorhanden (Ergebnis von `storage:link`)
- Auswirkung: Ohne Anmeldung abrufbar, wenn der Pfad bekannt ist. Besonders kritisch ist der Lieferschein: Pfad `storage/delivery-notes/delivery-note-ORD-JJJJ-NNNN.pdf` ist vorhersagbar (laufende Nummer) und enthält Name, Adresse und Telefon des Empfängers. Protokoll-PDFs, Unterschriften und Fotos nutzen UUIDs im Pfad, sind aber ebenfalls ohne Anmeldung lesbar, sobald die URL bekannt ist (z. B. aus dem E-Mail-Anhang oder Browserverlauf).
- Status: behoben. Lieferscheine, Protokoll-PDFs, Unterschriften und Fotos werden auf der privaten Disk `local` gespeichert (`Order::generateDeliveryNote`, `ProtocolController`, `ProtocolFinalizedMail`, `DeliveryNoteController`) und nur über Routen mit Anmeldung und Berechtigung ausgeliefert (`orders.print`, `protocols.pdf`). Fotos und Unterschriften werden nirgends direkt angezeigt, sondern nur ins Protokoll-PDF eingebettet; neue Auslieferungsrouten waren daher nicht nötig. `protocol.pdf` liefert 404, wenn die Datei fehlt.
- Bestehende Daten: `php artisan files:make-private` (mit `--dry-run` als Probelauf) verschiebt vorhandene Dateien von `public` nach `local`, ohne die Pfade in der Datenbank zu ändern. Er muss auf bestehenden Installationen einmalig ausgeführt werden (siehe [06-entwicklung-und-betrieb.md](06-entwicklung-und-betrieb.md)). Bis dahin sind alte Dateien über die Anwendung nicht abrufbar (404) und zugleich weiter über `/storage` erreichbar.
- Rest-Risiko ausgeräumt: Auch die Arbeitsanweisungen liegen jetzt auf der privaten Disk (`Admin\WorkInstructionController`, `WorkInstructionPublicController::serve`, Befehl `files:make-private` um `work-instructions/` erweitert). Eine deaktivierte Anweisung war vorher über ihre direkte `/storage`-URL (UUID-Pfad) abrufbar. Die öffentlichen Links `/anweisungen/...` bleiben unverändert. Tests: `tests/Feature/WorkInstructions/WorkInstructionTest.php` (12 Tests).
- Bewusst unverändert: Nur Avatare bleiben auf `public`, weil die Oberfläche sie direkt per URL einbindet.
- Tests: `tests/Feature/Files/PrivateFilesTest.php`.

### S2 Ausgabe von Ausnahmetexten

- Fundstelle: `DeliveryNoteController::generate`, `OrderPackingController` (`packItem`, `unpackItem`, `completeOrder`): `withErrors([$e->getMessage()])`
- Auswirkung: Interne Fehlertexte (Datenbank, Dateisystem) können an Benutzer gelangen. Dieselben Methoden werfen bewusst englische Fachtexte (`Cannot pack more than remaining quantity.`), die so ungeprüft und unübersetzt angezeigt werden.
- Korrektur: Eigene Fachausnahme oder Rückgabecodes mit Übersetzung; sonstige Ausnahmen loggen und allgemeine Meldung zeigen (wie in `ProtocolController`).
- Status: behoben. Neue Klasse `App\Exceptions\OrderWorkflowException` (erweitert `DomainException`) trägt einen Übersetzungsschlüssel samt Parametern und liefert den Text über `userMessage()`; `getMessage()` liefert den Schlüssel und dient nur dem Log. `Order::lockOrder`, `Order::markAsInDelivery`, `OrderItem::markAsPacked` und `OrderItem::unpack` werfen sie statt `\Exception` mit englischem Text. Die Controller (`OrderPackingController`, `DeliveryNoteController`) zeigen bei dieser Ausnahme den übersetzten Text (de und en), bei jeder anderen Ausnahme schreiben sie `Log::error(..., ['exception' => $e])` und zeigen nur `common.messages.unexpected_error`. Neue Schlüssel: `packing.messages.exceeds_remaining`, `packing.messages.not_all_items_packed`, `common.messages.unexpected_error`. Tests: `tests/Feature/Orders/ErrorHandlingTest.php`; zwei Wächter in `CodeConventionsTest` (kein `getMessage()` in `withErrors(` oder `->with(` der Controller, kein `throw new \Exception(` in den Models).

### S3 Kaskade beim Löschen von Benutzern

- Fundstelle: `orders.created_by` mit `onDelete('cascade')` (Migration `2025_11_18_180419`); `UserController::destroy` führt hartes `delete()` aus
- Auswirkung: Das Löschen eines Benutzers entfernt alle von ihm angelegten Aufträge samt Positionen und Historie. Pack-Nachweise (`packed_by`) werden dagegen nur auf `null` gesetzt.
- Status: behoben. Migration `2026_10_23_000003_set_orders_created_by_to_null_on_user_delete` macht `orders.created_by` nullable mit `nullOnDelete` (auf SQLite hoch, runter und wieder hoch geprüft). Aufträge, Positionen und Historie bleiben beim Löschen eines Benutzers erhalten; Auftragsdetail und Packansicht zeigen "Gelöschter Benutzer" (`orders.deleted_user`). Tests: `tests/Feature/Admin/UserDeletionTest.php`.
- Entscheidung: `set null` statt `restrict`, damit Benutzer weiterhin gelöscht werden können. Preis: Der Ersteller eines Auftrags geht beim Löschen verloren, ebenso der Benutzer in der Historie (war bereits so). **Ergänzung:** Die Alternative existiert jetzt: Benutzer lassen sich deaktivieren statt löschen (`users.is_active`, Button in der Benutzerliste, Details in [03-module.md](03-module.md)). Für ausgeschiedene Mitarbeiter ist das der empfohlene Weg, weil Ersteller und Packer erhalten bleiben. Tests: `tests/Feature/Admin/UserDeactivationTest.php`.

### S4 Lokale Werkzeugeinstellungen im Repository

- Fundstelle: `.claude/settings.local.json` ist versioniert (seit Commit `6787310`), enthält lokale Pfade und erlaubte Befehle (`E:\00_Git\RMS`, `php artisan migrate`, `tinker`, `rm`)
- Auswirkung: Kein Geheimnis gefunden, aber lokale Freigaben werden mit dem Team geteilt.
- Korrektur: Datei aus dem Index entfernen und in `.gitignore` aufnehmen.
- Status: behoben. Die Datei ist aus dem Index entfernt (`git rm --cached`, lokal bleibt sie erhalten) und `/.claude/settings.local.json` steht in `.gitignore`; ein gemeinsames `.claude/settings.json` bliebe weiter möglich. Der Verlauf wurde nicht umgeschrieben: Der Inhalt enthielt nur Befehlsfreigaben und lokale Pfade, keine Geheimnisse. Test: `CodeConventionsTest`.

### S5 CDN-Skript ohne Integritätsprüfung

- Fundstelle: `rental/protocol/form.blade.php`, `signature_pad@4.1.7` von `cdn.jsdelivr.net`
- Auswirkung: Die Seite erfasst rechtsverbindliche Unterschriften; ein manipuliertes CDN-Skript könnte diese abgreifen. Zusätzlich Abhängigkeit von externer Erreichbarkeit.
- Korrektur: Paket per npm einbinden und über Vite ausliefern, oder zumindest `integrity` und `crossorigin` setzen.
- Status: behoben. `signature_pad` (`^4.2.0`) ist eine npm-Abhängigkeit und wird über Vite im eigenen Bundle ausgeliefert; die Logik steht im Modul `resources/js/signature.js` (von `app.js` importiert). Das Protokollformular enthält kein Inline-Skript und keinen externen Skript-Tag mehr: Das Formular trägt `data-signature-form` und den übersetzten Hinweistext in `data-please-sign`. Hintergrund: Vite-Skripte sind Module und laufen erst nach dem Parsen, ein Inline-Skript fände `SignaturePad` nicht. Im Browser gegen eine Wegwerf-Datenbank geprüft: keine Anfrage an fremde Hosts, leere Unterschrift zeigt den Hinweis und sendet nicht, eine gezeichnete Unterschrift wird als PNG auf der privaten Disk gespeichert. Tests: `FrontendConsistencyTest` (kein Skript von fremdem Host in den Views, Modul und Abhängigkeit vorhanden), `ProtocolTest`.

### S6 Abhängigkeiten mit bekannten Schwachstellen

- Fundstelle: `composer.lock` (Stand September 2025, u. a. `laravel/framework` 12.27.0) und `package-lock.json`
- Nachweis vorher: `composer audit` meldete 55 Sicherheitshinweise in 15 Paketen (`league/commonmark` 14, `guzzlehttp/guzzle` 9, `dompdf/dompdf` 6, `laravel/framework` 5, `guzzlehttp/psr7` 4, `symfony/yaml` 4, `symfony/http-foundation` 3, `symfony/mime` 2, `symfony/routing` 2, dazu je einer in `league/flysystem`, `phpunit/phpunit`, `psy/psysh`, `symfony/mailer`, `symfony/polyfill-intl-idn`, `symfony/process`). `npm audit` meldete 12 Hinweise (1 kritisch in `shell-quote`, 10 hoch, u. a. `vite`, `axios`, `rollup`, `postcss`), alle in Build-Werkzeugen und Entwicklungsabhängigkeiten.
- Status: behoben. `composer update` innerhalb der erlaubten Versionsbereiche (101 von 141 Paketen aktualisiert, `symfony/polyfill-time` neu, u. a. `laravel/framework` 12.27.0 auf 12.70.2, `dompdf/dompdf` 3.1.0 auf 3.1.6, `symfony/*` auf 7.4.x, `guzzlehttp/guzzle` auf 7.16.1, `league/commonmark` auf 2.10.4) und `npm audit fix` ohne Hauptversionssprung. Danach melden `composer audit` und `npm audit` keine Hinweise mehr. Alle 228 Tests laufen weiter (darunter die PDF-Erzeugung für Lieferschein und Protokoll), das Frontend-Bundle wurde neu gebaut und enthält Alpine, CoreUI und die `x-cloak`-Regel.
- Entscheidung PHP-Version: Ein ungebundenes Update hat die effektive PHP-Mindestversion auf 8.4.1 angehoben (sechs Symfony-Pakete auf Version 8, `laravel/pint` auf 8.3), obwohl `composer.json` `^8.2` verspricht und die produktive PHP-Version nicht bekannt ist. Deshalb steht in `composer.json` `config.platform.php = 8.2.0`; Symfony bleibt auf 7.4, das die Sicherheitskorrekturen ebenfalls enthält, und die Sperrdatei bleibt mit PHP 8.2 kompatibel (`composer why-not php 8.2.0` meldet keinen Konflikt). Läuft die Produktion sicher auf PHP 8.4, kann diese Festlegung entfallen.
- Bewusst nicht eingespielt: neue Hauptversionen (Laravel 13, Pest 5, `spatie/laravel-activitylog` 5, `spatie/laravel-permission` 8, `intervention/image` 4, `laravel/tinker` 3, `barryvdh/laravel-debugbar` 4). Das sind eigene Vorhaben mit Migrationsschritten. Der Audit meldet für die eingesetzten Versionen nichts.
- Hinweis: Die Prüfung hat kein Wissen über künftige Hinweise. `composer audit` und `npm audit` gehören vor jedes Release.

## M Fachliche und technische Mängel

| ID | Beschreibung | Fundstelle | Korrektur |
|---|---|---|---|
| M1 | Behoben: Packen, Entpacken und Abschließen prüften den Status des Auftrags nicht; u. a. konnte der Abschluss einen ausgelieferten Auftrag wieder auf `packed` setzen. Jetzt nur im Status `in_progress`, im Controller mit übersetzter Meldung und zusätzlich als Regel in `OrderItem::markAsPacked`, `OrderItem::unpack` und `Order::lockOrder`. Tests: `tests/Feature/Orders/PackingStatusTest.php`. | `OrderPackingController`, `OrderItem`, `Order` | erledigt |
| M2 | Behoben: `delivery-notes/{order}/print` setzte den Auftrag ohne Statusprüfung auf `in_delivery`, auch aus `new`. Jetzt ist der Druck erst ab `packed` möglich; der Statuswechsel erfolgt nur beim ersten Druck und wird in `Order::markAsInDelivery` über `canTransitionTo` abgesichert. Erneuter Druck ändert nichts. Tests: `DeliveryNoteTest`. | `DeliveryNoteController::print`, `Order::markAsInDelivery` | erledigt |
| M3 | Behoben: Das Kontaktformular verwendete Alpine.js (`x-data`, `x-show`), das nicht geladen wurde; das Ein- und Ausblenden der Adressfelder je Kontakttyp griff nicht. Jetzt ist `alpinejs` eine npm-Abhängigkeit, `resources/js/app.js` startet Alpine, die ausgeblendeten Elemente haben `x-cloak` (Regel in `app.scss`). Im Browser geprüft (Wechsel auf Mieter blendet Ansprechpartner und Adresse aus, zurück auf Kunde blendet sie ein). Die Breeze-Komponenten `dropdown` und `modal` nutzen ebenfalls Alpine, werden aber nirgends verwendet (T3). Tests: `tests/Feature/Frontend/FrontendConsistencyTest.php`. | `resources/js/app.js`, `contacts/_form.blade.php` | erledigt |
| M4 | Behoben: Der Parameter `period` des PDF-Exports wurde validiert, aber nicht verwendet; der Report enthielt immer alle Daten. Außerdem scheiterte "alle Zeit" an der Validierung (`integer|min:1|max:365`), obwohl der Export-Link `all` übergibt. Jetzt gelten dieselben Zeiträume wie auf der Auswertungsseite, der Zeitraum wirkt auf Gesamtzahl, Status, Top-Artikel und Packleistung und steht im Kopf des Reports. Tests: `tests/Feature/Orders/AnalyticsExportTest.php`. | `OrderAnalyticsController::exportReport`, `report-pdf.blade.php` | erledigt |
| M5 | Behoben: Im Admin angelegte Benutzer hatten kein `email_verified_at` und waren durch `verified` ausgesperrt. Jetzt setzt `UserController::store` den Wert beim Anlegen. Bewusst unverändert: Ändert ein Benutzer seine eigene E-Mail im Profil, wird die Verifizierung zurückgesetzt (Breeze-Standard, setzt funktionierenden Mailversand voraus). Bereits angelegte, nicht verifizierte Benutzer bleiben unverändert und müssen einmalig verifiziert werden (`email_verified_at` setzen, z. B. über Tinker). Tests: `tests/Feature/Admin/UserAdministrationTest.php`. | `UserController::store`, `ProfileController::update` | erledigt |
| M6 | Behoben: Seeder erzeugten `tenants.*`/`customers.*` statt `contacts.*`, auf frischer Installation gehörte `contacts.*` keiner Rolle; `TestCustomerSeeder` verwendete das entfernte Model. Jetzt `contacts.*` in `RentalPermissionsSeeder` und `ContactPermissionsSeeder`, `TestContactSeeder` über `Contact`. Tests: `tests/Feature/Database/PermissionSeedersTest.php`. | `database/seeders/*` | erledigt, siehe [04-rechte-und-rollen.md](04-rechte-und-rollen.md) |
| M7 | Behoben: Die Schließen-Schaltflächen der Meldungen nutzten `data-bs-dismiss`; CoreUI wertet nur `data-coreui-dismiss` aus. In allen 8 Views umgestellt und im Browser geprüft (Klick auf das X schließt die Meldung). Ein Test verhindert, dass `data-bs-` wieder in eine View gelangt. | 8 Views in `resources/views` | erledigt |
| M8 | Behoben: `config/app.php` setzte `timezone` auf `UTC`; Nummernkreise, Tagesgrenzen (Dashboard "heute") und alle Zeitstempel richteten sich nach UTC, also 1 bis 2 Stunden hinter der Ortszeit. Jetzt `env('APP_TIMEZONE', 'Europe/Berlin')`, `APP_TIMEZONE=Europe/Berlin` steht in `.env.example`. Tests: `tests/Feature/Database/TimezoneAndScheduleTest.php` (Jahreswechsel der Nummern, Dashboard kurz nach Mitternacht, Zeitstempel in deutscher Zeit). **Daten:** Vorhandene Zeitstempel wurden nicht umgerechnet. Sie zeigen dieselbe Uhrzeit wie zuvor (UTC, 1 bis 2 Stunden vor Ortszeit), neue Einträge sind korrekt; Vermietungszeiten und Lieferdaten sind Ortszeit und unverändert. Eine Umrechnung wurde bewusst nicht gemacht, weil sie die Zeitzone der Datenbanksitzung voraussetzt (TIMESTAMP-Spalten in MariaDB) und nicht ohne Blick auf die Produktivdatenbank sicher ist. Vor dem Produktivbetrieb prüfen: `SELECT @@global.time_zone, @@session.time_zone`, und bei Bedarf `APP_TIMEZONE` setzen. | `config/app.php`, `.env.example` | erledigt |
| M9 | Behoben: Die Überschneidungsprüfung war inklusiv, eine Vermietung, die genau beim Ende der vorigen begann, wurde abgelehnt; die Logik stand doppelt in `store` und `update`. Jetzt halboffene Intervalle in `Rental::scopeOverlapping` und eine gemeinsame Methode `RentalController::hallIsBooked`. Die Eingaben werden zu Carbon geparst, weil SQLite und MariaDB Datumsangaben sonst unterschiedlich vergleichen (Text gegen Zahl). Tests: `tests/Feature/Rental/RentalOverlapTest.php`. | `RentalController`, `Rental` | erledigt |
| M10 | Behoben: Die Aufbewahrung ist mit 365 Tagen konfiguriert, `activitylog:clean` lief aber nie. Jetzt plant `routes/console.php` den Befehl täglich ein. Der Server braucht den Cronjob `* * * * * php artisan schedule:run`, sonst wird nichts gelöscht. Tests: `TimezoneAndScheduleTest` (Eintrag im Plan, alte Einträge werden gelöscht, `order_history` bleibt unberührt). | `routes/console.php` | erledigt |
| M11 | Behoben: `lang/en/mail.php` enthielt ein unmaskiertes Apostroph (`you're`) und ließ sich nicht parsen. Bei englischer Sprache scheiterten damit alle Mails, die das Mail-Layout nutzen (Passwort-Reset, E-Mail-Verifizierung, Protokoll-Mail) mit einem Syntaxfehler; bei der Standardsprache Deutsch fiel das nicht auf. Der Fehler bestand seit dem ersten Auftragsmodul und wurde bei der Prüfung aller Sprachdateien gefunden. Jetzt maskiert. Absicherung: `CodeConventionsTest` lädt alle Sprachdateien und vergleicht die Schlüssel von de und en, `TranslatedMessagesTest` rendert die englische Passwort-Reset-Mail. | `lang/en/mail.php` | erledigt |
| M12 | Offen: `OrderController::updateStatus` setzt nur den Status. Ein Wechsel auf `packed` von Hand lässt `packed_by` leer (der Auftrag fehlt in der Packleistung, `packerPerformance` verbindet über `packed_by`), ein Wechsel auf `in_delivery` lässt `delivery_note_printed_at` und den Lieferschein leer. Die Wege über Packen (`Order::lockOrder`) und Lieferscheindruck (`Order::markAsInDelivery`) setzen diese Nachweise. Nachweis: Probe-Test, `packed_by = NULL`, `delivery_note_printed_at = NULL`, `delivery_note_path = NULL` nach beiden Wechseln. | `OrderController::updateStatus`, Detailseite `order/orders/show` | Wechsel auf `packed` und `in_delivery` über dieselben Model-Methoden führen oder die Schaltflächen auf diese Wege beschränken; Entscheidung der Fachseite nötig |
| M13 | Offen: Die Regel `unique:articles,sku` und der Unique-Index zählen weich gelöschte Artikel mit. Wer einen Artikel löscht, kann dessen Artikelnummer nicht für einen neuen Artikel verwenden; der Fehler nennt keinen Verursacher, der gelöschte Artikel ist in der Oberfläche nicht sichtbar. Nachweis: Probe-Test, Validierungsfehler `sku` nach dem Löschen. | `ArticleController::store` und `update`, Migration `create_articles_table` | Artikel wiederherstellen können oder die Nummer beim Löschen freigeben (z. B. Regel mit `withoutTrashed()` plus zusammengesetzter Unique-Index); Entscheidung der Fachseite nötig |
| M14 | Offen: `OrderController::store` prüft `contact_id` nur mit `exists:contacts,id`. Das Formular bietet nur Kunden an, eine Anfrage mit der ID eines reinen Mieters (`ContactType::Tenant`) legt trotzdem einen Auftrag an. Nachweis: Probe-Test, ein Auftrag für einen Mieter wurde angelegt. | `OrderController::store` (Regel `contact_id`) | Regel auf Kunden einschränken (`Contact::customers()`), analog zum Formular; im Auftrag-Bearbeiten ist der Kontakt nicht änderbar |
| M15 | Offen: `RoleRequest::authorize` verlangt `role.edit`, `PermissionRequest::authorize` verlangt `perm.edit`, obwohl die Routen zum Anlegen `role.add` und `perm.add` verlangen. Ein Benutzer mit nur `role.add` oder `perm.add` besteht die Route und bekommt beim Speichern 403. Nachweis: Probe-Test, beide Anlegen-Aufrufe liefern 403. Im Alltag unauffällig, weil beide Rechte gemeinsam vergeben werden. | `app/Http/Requests/Admin/RoleRequest.php`, `PermissionRequest.php` | `authorize()` je nach Anlegen oder Ändern prüfen (`role.add` bzw. `role.edit`) |

## T Technische Schulden

- **T1 Tests:** Behoben. Die Suite ist vollständig grün (495 Tests). Neu sind 247 Tests für die bisher ungetesteten Bereiche: Auftragsanlage (`Orders/OrderCreationTest`), Bearbeitung (`OrderUpdateTest`), Statuswechsel mit allen erlaubten und verbotenen Schritten (`OrderStatusTest`), Liste, Filter, Detail und Historie (`OrderListTest`), Artikelstamm (`ArticleTest`), Auswertungsseite und JSON-Endpunkte (`AnalyticsTest`), Hallen mit Inventarzuordnung (`Rental/HallTest`), Vermietungen mit Übergabe- und Rückgabeprotokoll (`RentalTest`) sowie Lücken bei Rollen und Rechten (Validierung, Seiten, fremde Guards, Rechte je Aktion). Die Auswertungsabfragen laufen unter SQLite über eine nachgebildete Funktion `DATE_FORMAT` (`registerSqliteDateFormat()` in `tests/Pest.php`); die echte MariaDB-Funktion bleibt ungeprüft (siehe [06-entwicklung-und-betrieb.md](06-entwicklung-und-betrieb.md)). Gegenprobe: Mit vorübergehend gekippten Regeln (erlaubter Statuswechsel `new` nach `packed`, Löschschutz der Halle) wurden die zugehörigen Tests rot; dabei fiel auf, dass der Statustest seine Paare zunächst aus `canTransitionTo()` ableitete und mitgewandert wäre, daher stehen sie jetzt fest im Test. Es wurde kein Produktivcode geändert. Beim Schreiben der Tests gefundene Abweichungen stehen als M12 bis M15; sie sind bewusst nicht mit Tests festgeschrieben. Nicht abgedeckt bleibt die parallele Ausführung (K7) und echtes MariaDB-Verhalten.
- **T2 Stil:** Behoben. `vendor/bin/pint --test` meldete für das gesamte Projekt (`app`, `bootstrap`, `config`, `database`, `lang`, `routes`, `tests`) 101 von 292 PHP-Dateien; die frühere Zahl 54 von 118 galt nur für vier Verzeichnisse und eine ältere Pint-Version. Formatiert wurden 86 Dateien, die 15 alten Migrationen sind über `pint.json` ausgenommen (bestehende Migrationen werden nicht angefasst). Inhaltlich geprüft wurde ohne Tests: Syntaxprüfung aller geänderten Dateien, Anwendungsstart, gleiche Routenzahl (113), unveränderte Anzahl der Umlaute und Zeilenenden, sowie ein Vergleich der Namen, Zeichenketten und Zahlen jeder Datei vor und nach der Formatierung. Dabei zeigte sich genau eine inhaltliche Änderung: `OrderItem::markAsPacked(int $quantity = null)` wurde zu `?int $quantity = null` (in PHP 8.4 veraltet). Ein vollständiger Testlauf auf diesem Stand steht noch aus und ist vor dem Merge empfohlen.
- **T3 Toter Code:** Behoben. Entfernt wurden `LocaleController`, `RegisteredUserController` (beide nicht geroutet), die ungenutzten Breeze-Komponenten `application-logo`, `danger-button`, `dropdown`, `dropdown-link`, `modal` und `responsive-nav-link`, das ungenutzte Recht `app.settings` im Seeder sowie die Pakete `spatie/laravel-medialibrary` und `tuytoosh/active` (`composer remove`; die Treffer für "active" waren der eigene Scope `Article::active()`). Rest: Der Menüpunkt "Berechtigungen" in der Navigation ist weiter auskommentiert, die Route bleibt erreichbar. `tests/Feature/Quality/CodeConventionsTest.php` verhindert die Rückkehr.
- **T4 Übersetzungen:** Teilweise behoben. Alle benutzersichtbaren Meldungen der Controller (Erfolg, Fehler, `abort`) laufen jetzt über `__()` mit Schlüsseln unter `<bereich>.messages.*` in `lang/de` und `lang/en`; der Wortlaut für Deutsch blieb unverändert, die englischen Texte sind neu. Die zuvor ungenutzten Schlüssel `created`/`updated`/`deleted` mit abweichender Wortwahl (z. B. "Saal" statt "Halle") wurden durch die tatsächlich angezeigten Texte ersetzt. Offen: Feste deutsche Texte in den Views der Vermietung (Hallen, Inventar, Protokolle, Vermietungen), die englischen `order_history`-Beschreibungen sind fest, und die Benennung "Saal"/"Säle" in den Sprachdateien weicht von "Halle" in den Meldungen ab. Zwei Meldungen in `ProtocolController` (zu viele Fotos, ungültige Unterschrift) werden als Ausnahme geworfen und vom Controller als allgemeiner Fehler ausgegeben; der genaue Grund erreicht den Benutzer nicht (nur im Log).
- **T5 Aufbau:** Behoben. Alle Models verwenden `HasUuids` (der eigene Trait `HasUuid` ist entfernt). `\DB`, `\Log`, `\PDF`, `\Storage` und die voll qualifizierten Model-Namen sind durch Imports ersetzt, der doppelte Cache-Reset in den Rollen- und Rechtecontrollern ist entfernt (`forgetCachedPermissions()` genügt, wie der Quelltext von `permission:cache-reset` zeigt), die Validierungsregeln von `ContactController` und `RentalController` stehen je einmal. In `OrderController` unterscheiden sich `store` und `update` fachlich (Kundenanlage, Positionsabgleich); dort bleibt die Trennung bewusst.
- **T6 Lokale Umgebung:** Behoben. Im lokalen `vendor/` fehlte `spatie/laravel-activitylog` (laut `composer.lock` vorgesehen), `php artisan test` brach deshalb ab. `composer install` hat das behoben. Nach dem Entfernen von Paketen oder Klassen `composer dump-autoload -o` ausführen, weil `optimize-autoloader` aktiv ist.

## Empfohlene Reihenfolge

1. K1 bis K7, S1 bis S6, M1 bis M11, T1, T2, T3, T5 und T6 sind erledigt, T4 teilweise. Neu offen: M12 (Mittel), M13, M14 und M15 (Niedrig).
2. S1 (jetzt einschließlich der Arbeitsanweisungen) und S3 sind erledigt (auf bestehenden Installationen `php artisan files:make-private` und `php artisan migrate` ausführen). Das Deaktivieren von Benutzern ist umgesetzt; ausgeschiedene Mitarbeiter sollten deaktiviert statt gelöscht werden.
3. Einen Parallel-Test der Nummernvergabe auf MariaDB durchführen (K7). M3 und M7 sind erledigt.
4. Vor dem Produktivbetrieb: Zeitzone der Datenbank prüfen (M8) und den Cronjob für `schedule:run` einrichten (M10).
5. T2 ist erledigt (eigener formaler Commit); `pint --test` ist jetzt Pflicht vor jedem Merge. T1 ist erledigt; die Tests für neue Funktionen kommen mit der Funktion.
6. S6 ist erledigt; `composer audit` und `npm audit` vor jedem Release wiederholen.
