# 07 Coding Guidelines

Dieses Dokument legt fest, wie im Projekt Code geschrieben wird. Es verbindet drei Quellen:

1. **Organisationsregeln** (verbindlich, haben Vorrang vor allem anderen)
2. **Automatisch prüfbare Regeln** (`.editorconfig`, Laravel Pint)
3. **Projektkonventionen**, wie sie sich im Bestand zeigen oder künftig gelten sollen

Kennzeichnung: **MUSS** gilt ohne Ausnahme, **SOLL** gilt für neuen und geänderten Code, **KANN** ist Empfehlung. Bestehende Abweichungen sind in Abschnitt 13 aufgelistet; sie werden nicht flächig umgebaut, sondern beim Anfassen der jeweiligen Datei angeglichen.

## 1. Organisationsregeln (verbindlich)

| Regel | Inhalt |
|---|---|
| Umlaute | `ä ö ü Ä Ö Ü ß` MÜSSEN erhalten bleiben. Beim Bearbeiten niemals in `ae`, `oe` usw. wandeln oder entfernen; Suchtexte beim Ersetzen müssen exakt inklusive Umlauten übereinstimmen. Dateien sind UTF-8. |
| Kommentare | Im Code nur reines ASCII plus deutsche Umlaute. Keine Emojis, keine Sonderzeichen, kein Halbgeviertstrich (en dash) und kein Geviertstrich (em dash); stattdessen ein normaler Bindestrich `-`. |
| Schreibgeschützte Dateien | Ist eine Datei schreibgeschützt (z. B. TFS-Auschecken erforderlich), wird das sofort gemeldet und auf das manuelle Auschecken gewartet. Der Schreibschutz wird nie selbst entfernt (kein `attrib`, kein `chmod`). |
| Geheimnisse | Passwörter, API-Schlüssel, Tokens, Verbindungszeichenfolgen, Webhooks und private Schlüssel gehören nie in Code, Logs, Tests, Screenshots oder Dokumentation, sondern in `.env` bzw. einen Secret-Store. Ein im Repository oder Verlauf aufgetauchtes Geheimnis ist zu widerrufen und zu ersetzen; Löschen aus der Datei genügt nicht. |
| Personenbezogene Daten | Keine personenbezogenen Daten (Namen, Adressen, E-Mail, Telefon, Kunden-, Mitarbeiter- oder Gesundheitsdaten) in Prompts, Tickets, Testdaten, Seedern oder Beispielen an KI-Werkzeuge. Für Tests und Beispiele werden anonymisierte oder erfundene Daten verwendet. |

## 2. Formatierung

Quelle: `.editorconfig` und Laravel Pint.

- UTF-8, Zeilenende LF, Einrückung 4 Leerzeichen (YAML: 2), Zeilenabschluss mit Leerzeile am Dateiende, keine Leerzeichen am Zeilenende (Markdown ausgenommen). **MUSS**
- PHP folgt dem Laravel-Pint-Standardpreset (PSR-12-basiert). Eine `pint.json` existiert nicht; das Standardpreset gilt. **SOLL**
- Vor jedem Commit: `vendor/bin/pint --test <geänderte Dateien>`; Korrekturen nur an den eigenen Änderungen, keine Formatierungsrunde über unbeteiligte Dateien im selben Commit. **SOLL**
- Kurze Array-Syntax, einfache Anführungszeichen, `use`-Anweisungen alphabetisch und ohne ungenutzte Imports, keine voll qualifizierten Klassennamen im Code (`\DB::`, `\Log::`, `\App\Models\...`), sondern `use` am Dateikopf. **SOLL**
- Keine auskommentierten Codeblöcke einchecken (Beispiel im Bestand: Menüpunkt "Berechtigungen"). **SOLL**

## 3. Namensgebung und Struktur

| Element | Regel | Beispiel |
|---|---|---|
| Namespace | Nach Fachdomäne: `Admin`, `Contact`, `Order`, `Rental`; Models und Controller liegen im gleichen Domänen-Namespace | `App\Models\Order\Order`, `App\Http\Controllers\Order\OrderController` |
| Klassen | `PascalCase`, Controller mit Suffix `Controller`, Form Requests mit `Request`, Enums im Singular ohne Suffix | `ContactController`, `RoleRequest`, `OrderStatus` |
| Models | Singular; Tabelle Plural `snake_case` (Ausnahme mit explizitem `$table`: `OrderHistory` auf `order_history`) | `Contact` auf `contacts` |
| Enum-Fälle | Neue Enums: `PascalCase` wie `ContactType::Tenant`; im Bestand nutzt `OrderStatus` `SCREAMING_CASE`. Werte sind `snake_case`-Strings. | `'in_progress'` |
| Methoden und Variablen | `camelCase`; Controller-Aktionen nach Laravel-Resource-Konvention (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`) plus sprechende Zusatzaktionen | `toggleStatus`, `updateStatus` |
| Routen | Pfade `kebab-case`, Namen `bereich.aktion` (`orders.update-status`); Ressourcen-Parameter in `camelCase` | `/inventory-items/{inventoryItem}` |
| Berechtigungen | `bereich.aktion`, Aktionen `view`, `create`, `edit`, `delete` plus fachliche Aktionen in `kebab-case`; Bereich im Plural | `contacts.toggle-status` |
| Migrationen | Laravel-Zeitstempel, sprechender Name; **bestehende Migrationen werden nie geändert**, Änderungen immer als neue Migration | `2026_04_12_000001_create_contacts_table` |
| Seeder | Je Bereich ein Seeder `<Bereich>PermissionsSeeder`; Testdaten in `Test<Bereich>Seeder`; idempotent (`firstOrCreate`, `syncPermissions`) | `ContactPermissionsSeeder`, `TestContactSeeder` |
| Views | Ordner `bereich/ressource`, Dateien `kebab-case`; Teilformulare mit Unterstrich (`_form`) | `order/orders/_form.blade.php` |
| Sprachdateien | Eine Datei je Bereich, in `de` und `en` mit identischen Schlüsseln | `lang/de/contacts.php` |
| Tests | Pest, Dateiname `<Bereich>Test.php` in `tests/Feature/<Bereich>/`, Testname als vollständiger Satz | `test('orders cannot be deleted once packed', ...)` |

## 4. Controller

- Controller bleiben dünn: Anfrage prüfen, Fachlogik aufrufen, Antwort zurückgeben. Neue umfangreichere Fachlogik gehört in das Model oder eine eigene Klasse (z. B. `app/Services/...`), nicht in 150-Zeilen-Controller-Methoden (Gegenbeispiel: `ProtocolController::sign`). **SOLL**
- Validierung: Neue Controller und alle Aktionen mit mehr als vier Feldern verwenden Form Requests in `app/Http/Requests/<Domäne>/`. Regeln als Arrays, nicht als `|`-Strings. **SOLL**
- Autorisierung: Pro Route per `permission:`-Middleware, bei Gruppen mit Basisrecht zusätzlich Aktionsrecht je Route (siehe [04-rechte-und-rollen.md](04-rechte-und-rollen.md)). Jede neue Route MUSS ein Recht verlangen, außer sie ist bewusst öffentlich; öffentliche Routen sind im Pull Request zu begründen. **MUSS**
- Route-Model-Binding: Der Name des Controller-Parameters MUSS dem Routenparameter in `camelCase` entsprechen (`{inventoryItem}` und `InventoryItem $inventoryItem`). Ein abweichender Name (`$inventory_item`) bindet nicht und liefert ein leeres Model (K4). **MUSS**
- Rückgabetypen deklarieren (`View`, `RedirectResponse`, `JsonResponse`). **SOLL**
- Fehlerbehandlung: Erwartbare Fachfehler als `back()->withErrors(...)`; unerwartete Ausnahmen nicht pauschal mit `catch (\Exception $e)` und `$e->getMessage()` an Benutzer ausgeben (Informationsabfluss), sondern loggen und eine allgemeine Meldung zeigen. **SOLL**
- Statusabhängige Aktionen (Packen, Drucken, Löschen, Abschließen) prüfen den Status des Datensatzes im Controller mit übersetzter Meldung und zusätzlich als Invariante im Model (Ausnahme bei Verstoß). Zulässige Statusübergänge richten sich nach `OrderStatus::canTransitionTo`; kein Übergang ohne diese Prüfung (M1, M2). **SOLL**
- Nach schreibenden Aktionen immer Redirect (Post-Redirect-Get).
- Flash-Meldungen: Im Bestand gibt es `ok`, `status` und `error`. Für neuen Code gilt `ok` für Erfolg und `error` für Fehler; `status` nur in den Admin-Bereichen, in denen es schon verwendet wird, bis vereinheitlicht wird. **SOLL**
- Abfragen: Listen immer paginieren; Eager Loading (`with`) für alle in der View genutzten Relationen; für reine Zählungen `withCount` statt Relation laden (Gegenbeispiel: `OrderController::index` lädt alle `items`). Suchbegriffe in `LIKE` sind zu maskieren oder bewusst zu belassen. **SOLL**

## 5. Models und Datenbank

- Primärschlüssel: UUID mit Laravels `Illuminate\Database\Eloquent\Concerns\HasUuids`. Der eigene Trait `App\Models\Concerns\HasUuid` ist Altbestand der Vermietung und wird nicht für neue Models verwendet. **SOLL**
- `$fillable` explizit pflegen, kein `$guarded = []`. `casts` für Enums, Booleans, Datum, Dezimalwerte und JSON; Enum-Casts statt String-Vergleichen (`$contact->type === ContactType::Tenant`). **SOLL**
- Relationen mit Rückgabetyp (`BelongsTo`, `HasMany`) und Importen. Jede Relation, deren Zielklasse in einem anderen Namespace liegt, braucht einen `use`-Import; ein fehlender Import erzeugt keinen Syntaxfehler, aber eine Laufzeitfehlermeldung (K1). **MUSS**
- Nach Namespace-Umzügen oder Umbenennungen von Relationen die Verwendungen suchen: PHP, Blade (`->relation`), Eager-Loading-Strings (`'rental.tenant'`), Seeder, Mails, PDFs. Es gibt keine statische Prüfung, die das auffängt (K1 bis K5, M6). **MUSS**
- Fachlogik, die den angemeldeten Benutzer (`auth()`) oder den Request braucht, nicht in Models verstecken; der Aufrufer übergibt den Benutzer. Bestehende Ausnahmen: `Order::logHistory`, `OrderItem::markAsPacked`. **KANN**
- Migrationen: `foreignUuid()` bzw. `uuid()` für Verweise auf UUID-Tabellen, Fremdschlüssel mit bewusstem `onDelete` (`cascade`, `set null`, `restrict`) und Index auf Fremdschlüssel- und Filterspalten. Datenmigrationen idempotent halten oder die Nicht-Umkehrbarkeit ausdrücklich (`throw` in `down()`) und im Kommentar begründen. **SOLL**
- Datenbankspezifisches (`DATE_FORMAT`, `orderByRaw` mit Aliasen, `ENUM`) nur mit Hinweis im Kommentar verwenden, da die Tests auf SQLite laufen. **SOLL**
- Nummernkreise werden ausschließlich über `App\Services\NumberSequence::next()` vergeben (Zählertabelle `number_sequences`, Transaktion mit Zeilensperre). Nie "letzte Nummer plus 1" aus der Datenbank lesen und nie nach `created_at` sortieren, um die "letzte" Nummer zu finden; gleichzeitige Anfragen und mehrere Datensätze in derselben Sekunde erzeugen sonst doppelte Nummern (K7). **MUSS**
- Löschen: Wo fachlich nachvollziehbar bleiben muss (Aufträge, Artikel, Protokolle), Soft Deletes statt hartem Löschen. Modelle mit Soft Delete werden in Auswertungen und Unterabfragen immer über die Beziehung zum Hauptmodell gefiltert (`whereHas`), damit zugehörige Zeilen gelöschter Datensätze nicht mitzählen. Ein Observer darf nach dem Löschen nur Daten schreiben, die den Datensatz nicht per Fremdschlüssel voraussetzen (K6). Fremdschlüssel `cascade` auf Benutzer (`orders.created_by`) nur bewusst setzen (S3). **SOLL**
- Rechte neu anlegen: Neue Rechte entstehen per Migration (wirken auch auf bestehenden Installationen) und werden zusätzlich im passenden Seeder Rollen zugeordnet (frische Installation). Benennt man Rechte um, müssen Migration, Seeder, Routen, Views und Doku gemeinsam angepasst werden (M6). **SOLL**

## 6. Views und Frontend

- UI ausschließlich mit CoreUI 5 / Bootstrap 5 und Font Awesome (`fas fa-...`); keine zusätzlichen UI-Bibliotheken ohne Abstimmung. **SOLL**
- CoreUI-Komponenten verwenden `data-coreui-*`-Attribute. `data-bs-*` (im Bestand an 8 Stellen) funktioniert mit CoreUI nicht, weil dessen Skripte nur die eigenen Attribute auswerten; neue Views nutzen `data-coreui-*`. **SOLL**
- Texte nie fest in Views oder Controllern, immer `__('bereich.schluessel')`. Gilt auch für Flash-Meldungen und Validierungsnachrichten. **MUSS** für neuen Code
- Layout über `<x-app-layout>`; öffentliche Seiten über `<x-public-layout>`. Navigation und Aktionsschaltflächen mit `@can`. **SOLL**
- Alpine.js wird nicht ausgeliefert. `x-data`/`x-show` nur verwenden, wenn Alpine ordentlich über Vite eingebunden wird (M3). **MUSS**
- Skripte: `@push('scripts')` statt `<script>` mitten im Markup; keine externen CDN-Skripte, sondern über npm und Vite einbinden (Bestand: `signature_pad` per CDN). **SOLL**
- Ausgabe mit `{{ }}`; `{!! !!}` nur für nachweislich vertrauenswürdige Inhalte. **MUSS**
- Datumsformat in der Oberfläche `d.m.Y` bzw. `d.m.Y H:i`; Zeitzone der Anzeige beachten (Anwendungszeitzone ist UTC). **SOLL**
- Formulare: `@csrf`, bei PUT/PATCH/DELETE `@method`, Fehlerausgabe je Feld, `old()` für Eingabewerte; zerstörerische Aktionen mit Bestätigung.

## 7. Mehrsprachigkeit

- Jede Textänderung in `lang/de` MUSS in `lang/en` gespiegelt werden (identische Schlüssel). **MUSS**
- Schlüsselstruktur: `datei.gruppe.schluessel`, Meldungen in `messages`, Status in `statuses`. Enum-Beschriftungen über `label()` am Enum.
- Deutsch ist Standardsprache. Lieferscheine werden immer deutsch erzeugt. Historieneinträge (`order_history.description`) sind bislang englisch, neue Einträge sollen über Übersetzungsschlüssel mit Metadaten entstehen, damit sie in der Sprache des Betrachters erscheinen. **KANN**

## 8. Sicherheit

- Dateien mit personenbezogenem oder geschäftlichem Inhalt (Lieferscheine, Protokolle, Unterschriften, Fotos) auf einer privaten Disk speichern und nur über eine berechtigte Route ausliefern. Die Disk `public` ist für Inhalte, die ohnehin öffentlich sein sollen (S1). **MUSS** für neuen Code
- Uploads immer validieren (Typ, Größe, Anzahl); Dateinamen serverseitig erzeugen (UUID), nie vom Client übernehmen. Pfade vor dem Ausliefern gegen Verzeichniswechsel prüfen (Vorbild: `DeliveryNoteController::validateStoragePath`).
- Mass Assignment nur über validierte Daten (`$request->validate()` oder Form Request), nie `$request->all()`.
- Rohe SQL-Fragmente nur mit Parameterbindung oder fester Whitelist (Vorbild: `Order::ordersOverTime`).
- Aufwendige oder externe Endpunkte (PDF, JSON, Download) mit `throttle` versehen (Vorbild: Auswertung).
- Benutzereingaben nie in `Content-Disposition` ohne Escaping einsetzen; im Bestand wird `addslashes` verwendet, besser ist eine Dateinamensbereinigung.
- Anmeldung, Rechte- und Benutzeränderungen sind im Aktivitätslog nachvollziehbar zu halten (Log `admin`).

## 9. Kommentare und Dokumentation im Code

- Kommentare sparsam und nur für das Warum, nicht für das Was. Sprache der Kommentare: Deutsch (Bestand ist gemischt). Zeichensatz siehe Abschnitt 1. **SOLL**
- PHPDoc nur dort, wo Typen nicht aus der Signatur hervorgehen (z. B. `@return list<string>`).
- **Dokumentation wird mit jeder Änderung aktualisiert, sofern sie davon betroffen ist.** Die Anpassung der Dokumente in `docs/` gehört in denselben Commit bzw. Pull Request wie die Codeänderung; ein Pull Request ohne nötige Doku-Anpassung gilt als unvollständig. **MUSS**
- Zuordnung, welches Dokument bei welcher Änderung zu prüfen ist:

| Änderung | Zu prüfende Dokumente |
|---|---|
| Neue oder geänderte Tabelle, Spalte, Index, Enum, Nummernkreis, Migration | [02-datenmodell.md](02-datenmodell.md) |
| Neue oder geänderte Fachregel, Ablauf, Controller, Model, Mail, PDF | [03-module.md](03-module.md), bei Dateispeicher und Aufbau auch [01-architektur.md](01-architektur.md) |
| Neues, umbenanntes oder entferntes Recht, Rolle oder Seeder | [04-rechte-und-rollen.md](04-rechte-und-rollen.md) |
| Neue, geänderte oder entfernte Route | [05-routen.md](05-routen.md) (Tabellen aus `php artisan route:list --except-vendor --json` neu erzeugen) |
| Setup, Befehle, Umgebungsvariablen, Teststand, Betrieb | [06-entwicklung-und-betrieb.md](06-entwicklung-und-betrieb.md) |
| Neue oder geänderte Konvention, neue Abweichung im Bestand | dieses Dokument |
| Befund behoben oder neu entdeckt | [08-bekannte-probleme.md](08-bekannte-probleme.md) (Status je Punkt, Prioritätstabelle, Empfohlene Reihenfolge) |
| Wichtige Eckdaten (Teststand, Kernaussagen) | [README.md](README.md) |

- Ist keine Dokumentation betroffen, wird das in der Commit-Nachricht oder im Pull Request kurz vermerkt ("Doku: keine Änderung nötig"). **SOLL**
- Beim Ändern von Doku-Dateien gilt Abschnitt 1 (Umlaute erhalten, keine Gedankenstriche). Ersetzungen in Dokumenten mit Werkzeugen nie ohne anschließende Sichtprüfung des Ergebnisses durchführen. **SOLL**

## 10. Tests

- Pest mit `RefreshDatabase`. Jede neue Fachfunktion bekommt mindestens einen Feature-Test für den Erfolgsfall, den Berechtigungsfall (`403` ohne Recht) und die wichtigste Fehlerbedingung. **SOLL**
- Fehlerbehebungen beginnen mit einem Test, der den Fehler reproduziert.
- Hilfsfunktionen aus `tests/Pest.php` verwenden: `userWithPermissions([...])`, `makeContact()`, `makeOrder()`. Seeder-Tests rufen die Seeder über `$this->seed(...)` auf (nicht direkt, da sie `$this->command` nutzen).
- Testdaten per Factory und frei erfundene Werte; keine echten Personen- oder Kundendaten, E-Mail-Domains aus `.example` oder `.test`. **MUSS**
- Tests müssen unter SQLite laufen; MariaDB-spezifische Abfragen werden gekapselt oder gesondert markiert.
- Vor dem Merge: `composer test` und `vendor/bin/pint --test` grün.

## 11. Git und Zusammenarbeit

- Hauptzweig ist `dev` (Remote `origin/dev`); Arbeit in Feature-Branches, Merge per Pull Request. **SOLL**
- Commit-Nachrichten im Format `typ: Kurzbeschreibung` mit den Typen `feat`, `fix`, `refactor`, `docs`, `test`, `chore`. Der Verlauf ist im Format teils uneinheitlich ("fixes", "restructuring and modifications"); neue Commits halten das Format ein. Die Beschreibung ist Deutsch oder Englisch, aber im Branch einheitlich (zuletzt Deutsch). Ein Commit behandelt ein Thema; Umbauten und Verhaltensänderungen nicht mischen. **SOLL**
- Nicht einchecken: `.env`, lokale Werkzeugeinstellungen (`.claude/settings.local.json`), Build-Ausgaben, Zugangsdaten, echte Daten. **MUSS**
- Schreibgeschützte Dateien: siehe Abschnitt 1.

## 12. Checkliste für neue Funktionen

1. Fachbereich bestimmen und Namespace, Route-Datei, View-Ordner, Sprachdatei danach wählen.
2. Migration anlegen (UUID, Fremdschlüssel, Indizes), Model mit `HasUuids`, `$fillable`, `casts`, Relationen mit Importen.
3. Rechte definieren, per Migration oder Seeder anlegen, Rollen zuordnen, Route-Middleware setzen, `@can` in Views.
4. Form Request und Controller, Texte über `lang/de` und `lang/en`.
5. Dateien privat speichern, wenn sie Personenbezug haben.
6. Tests schreiben, Pint laufen lassen.
7. `docs/` aktualisieren (Datenmodell, Rechte, Routen, Modulbeschreibung, bekannte Probleme) gemäß der Zuordnung in Abschnitt 9. Gilt für jede Änderung, nicht nur für neue Funktionen.

## 13. Abweichungen im Bestand

Messung mit Laravel Pint (Standardpreset, `vendor/bin/pint --test app routes database tests`): **54 von 118 PHP-Dateien** weichen ab (Stand vor den Korrekturen K1 bis K5 und M6; neue und angepasste Seeder- und Testdateien sind sauber). Schwerpunkte:

| Bereich | Typische Abweichung |
|---|---|
| `app/Models/Rental/*`, `Concerns/HasUuid.php` | Einzeilige Klassen und Methoden, fehlende Leerzeilen, `class X {` in derselben Zeile |
| `app/Http/Controllers/Rental/*`, `Order/*` | Leerzeichen um Operatoren und Argumente, ungenutzte Imports, `!`-Operator ohne Leerzeichenregel, uneinheitliche Anführungszeichen |
| Ältere Migrationen | Klassenkopf und Klammerstil, Leerzeichen in Leerzeilen |
| `routes/web.php`, `routes/rental.php` | Leerzeichen nach Kommas, doppelte Leerzeilen |
| `app/Models/Order/OrderItem.php` | `int $quantity = null` ist ab PHP 8.4 als implizit nullable veraltet (`?int $quantity = null`) |

Weitere inhaltliche Abweichungen von den Regeln oben:

| Regel | Abweichung | Fundstelle |
|---|---|---|
| Voll qualifizierte Klassen vermeiden | `\DB`, `\Log`, `\PDF`, `\Storage`, `\Artisan` direkt im Code | `ProtocolController`, `RentalController`, `Order`, `RoleController` |
| Texte übersetzen | Feste deutsche Meldungen in Controllern | `ArticleController`, `DeliveryNoteController`, `OrderPackingController`, `HallController`, `InventoryItemController`, `ProtocolController`, `RentalController`, `PermissionController`, `RoleController` |
| Form Requests | Fachcontroller validieren inline; Form Requests gibt es nur für Rollen, Rechte, Login, Profil | alle Fach-Controller |
| Eine UUID-Variante | Zwei Mechanismen (`HasUuids` und `HasUuid`) | Rental-Models |
| Einheitliche Flash-Schlüssel | `ok`, `status`, `error` gemischt | Controller und Views |
| `data-coreui-*` | `data-bs-dismiss` in 8 Stellen | Views |
| Keine toten Codeteile | `LocaleController`, `RegisteredUserController` (nicht geroutet), Recht `app.settings`, Pakete `spatie/laravel-medialibrary` und `tuytoosh/active` | siehe [08-bekannte-probleme.md](08-bekannte-probleme.md) |
| Fachlogik nicht im Controller | `ProtocolController::sign` (PDF, Mail, Statuswechsel, Transaktion in einer Methode) | `ProtocolController` |
| Doppelter Code | Überschneidungsprüfung in `store` und `update`, Validierungsregeln je in `store` und `update` doppelt | `RentalController`, `ContactController` |
| Doppelter Cache-Reset | `Artisan::call('permission:cache-reset')` und `forgetCachedPermissions()` hintereinander | Admin-Controller |

Vorgehen: Keine flächige Formatierungsrunde. Beim Ändern einer Datei werden deren Verstöße in einem getrennten Commit (`refactor: Stil angleichen`) behoben, damit Diff und Verhaltensänderung nachvollziehbar bleiben.
