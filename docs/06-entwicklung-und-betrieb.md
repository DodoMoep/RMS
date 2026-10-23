# 06 Entwicklung und Betrieb

## Voraussetzungen

- PHP 8.2 oder neuer mit den üblichen Laravel-Erweiterungen sowie `pdo_mysql` (Betrieb) bzw. `pdo_sqlite` (Tests); `gd` für Intervention Image (GD-Treiber in `ProfileController`, WebP-Ausgabe)
- Composer 2, Node.js mit npm
- MariaDB (Entwicklung und Betrieb)
- Lokal wird PHP über Laravel Herd bereitgestellt (`php` verweist auf `~/.config/herd/bin`; nicht im Git-Bash-Pfad, daher in PowerShell oder `cmd` aufrufen)

## Einrichtung

```bash
composer install
npm install
copy .env.example .env      # unter PowerShell: Copy-Item .env.example .env
php artisan key:generate
# DB_DATABASE, DB_USERNAME, DB_PASSWORD, MAIL_* in .env setzen
php artisan migrate
php artisan storage:link
```

Erstausstattung mit Rechten und Rollen (siehe Hinweise in [04-rechte-und-rollen.md](04-rechte-und-rollen.md), die Seeder sind teilweise veraltet):

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder    # erwartet, dass ein Benutzer mit ID 1 existiert
php artisan db:seed --class=OrderPermissionsSeeder
php artisan db:seed --class=RentalPermissionsSeeder
php artisan db:seed --class=ContactPermissionsSeeder
```

`DatabaseSeeder` legt über die Factory einen Benutzer an; die Factory setzt `email_verified_at`, im Admin-Formular angelegte Benutzer haben dagegen keine bestätigte E-Mail (siehe M5). Optional für Testdaten in der Entwicklung: `php artisan db:seed --class=TestContactSeeder` (drei erfundene Kontakte). `DatabaseSeeder` ruft die Rechte-Seeder nicht auf.

## Alltagsbefehle

| Zweck | Befehl |
|---|---|
| Entwicklungsserver mit Queue und Vite | `composer dev` (startet `php artisan serve`, `queue:listen --tries=1`, `npm run dev`) |
| Nur Frontend (Hot Reload) | `npm run dev` |
| Frontend-Build | `npm run build` |
| Migrationen | `php artisan migrate` |
| Routen prüfen | `php artisan route:list --except-vendor` |
| Caches leeren | `php artisan optimize:clear` (bei Rechteproblemen zusätzlich `php artisan permission:cache-reset`) |
| Stil prüfen | `vendor/bin/pint --test` bzw. `vendor/bin/pint` zum Korrigieren |
| Tests | `composer test` (leert die Config, dann `php artisan test`) |
| Dateien auf die private Disk verschieben | `php artisan files:make-private` (zuvor `--dry-run` zur Kontrolle; wiederholbar) |
| Aktivitätslog bereinigen | `php artisan activitylog:clean` (nicht automatisiert, siehe unten) |

## Tests

- Framework: Pest 3 mit `RefreshDatabase` für `tests/Feature` (`tests/Pest.php`); `phpunit.xml` setzt SQLite in-memory, Mail `array`, Queue `sync`, Cache und Session `array`.
- Vorhanden sind die Breeze-Standardtests (Login, Registrierung, Passwort, E-Mail-Verifizierung, Profil), zwei Beispieltests und Feature-Tests für Kontakte (`Contacts/`), Packübersicht, Packstatus, Lieferschein und Auftragslöschung (`Orders/`), Inventar und Protokolle (`Rental/`), Seeder und Nummernkreise (`Database/`), Benutzerverwaltung (`Admin/`) sowie private Dateispeicherung und der Verschiebe-Befehl (`Files/`). Ohne Tests sind Auftragsanlage und -bearbeitung, Statuswechsel über `updateStatus`, Vermietungen, Auswertung, Rollen- und Rechteverwaltung und Arbeitsanweisungen.
- Hilfsfunktionen in `tests/Pest.php`: `userWithPermissions([...])` (verifizierter Benutzer mit genau diesen Rechten), `makeContact()` und `makeOrder()` (erfundene Testdaten).
- Stand 2026-10-23: 110 Tests grün, 3 rot. Die roten Tests waren schon vorher rot und sind nicht Teil der Fachänderungen: `RegistrationTest` (zwei Tests; die Route `/register` existiert nicht) und `ExampleTest` (`/` leitet nicht angemeldete Besucher auf den Login um, erwartet wird 200).
- Hinweis: Fehlte im lokalen `vendor/` `spatie/laravel-activitylog` (steht in `composer.json` und `composer.lock`), bricht der Lauf mit `Trait "Spatie\Activitylog\Traits\LogsActivity" not found` ab. Abhilfe: `composer install`.
- Einschränkung für künftige Tests: `Order::ordersOverTime()` nutzt `DATE_FORMAT` (MySQL/MariaDB) und läuft nicht auf SQLite. Auswertungstests brauchen eine MariaDB-Testdatenbank oder eine portable Abfrage.

Hinweis zu den Nummernkreis-Tests (`tests/Feature/Database/NumberSequenceTest.php`): SQLite läuft in einem Prozess und kann gleichzeitige Anfragen nicht nachstellen. Die Tests sichern das Verhalten (fortlaufend, keine Wiederverwendung, Weiterzählen, Jahreswechsel), die Sperre über `lockForUpdate` wirkt erst auf MariaDB.

Empfohlene nächste Tests (nach Risiko): Auftragsanlage und Statusübergänge, Packen und Abschluss, Überschneidungsprüfung bei Vermietungen, Berechtigungsprüfung je Rolle.

## Umgebungsvariablen (Auswahl)

Die Werte stehen in `.env` und dürfen nicht ins Repository (`.env` ist in `.gitignore`). Vorlage: `.env.example`.

| Variable | Bedeutung | Vorgabe in der Vorlage |
|---|---|---|
| `APP_NAME` | Anwendungsname | `Rosenau Management System` |
| `APP_ENV`, `APP_DEBUG`, `APP_URL` | Umgebung, Debug, Basis-URL (auch für `/storage`-URLs) | `production`, `false`, `http://localhost` |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE` | Sprachen | `de`, `en` |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Datenbank | `mariadb`, `127.0.0.1`, `3306` |
| `SESSION_DRIVER`, `QUEUE_CONNECTION`, `CACHE_STORE` | Technische Treiber | jeweils `database` |
| `MAIL_*` | SMTP für Verifizierung, Passwort-Reset und Protokoll-Mails | SMTP auf `127.0.0.1:25` |
| `LOG_STACK`, `LOG_SLACK_WEBHOOK_URL` | Logging (Datei plus Slack) | `single,slack`, Webhook leer |
| `ACTIVITY_LOGGER_ENABLED` | Aktivitätslog an/aus | `true` (Code-Vorgabe) |

Der Slack-Webhook ist ein Geheimnis: nur in `.env` oder einem Secret-Store ablegen, nie einchecken.

## Betriebshinweise

- Dateispeicher: Nach dem Deployment `php artisan storage:link` ausführen (für Avatare und Arbeitsanweisungen auf der Disk `public`). Die Verzeichnisse `storage/` und `bootstrap/cache/` müssen für den Webserver-Benutzer beschreibbar sein.
- Queue: Mails (`ProtocolFinalizedMail`) werden synchron versendet, es werden keine Jobs eingestellt. Ein Queue-Worker ist für den aktuellen Funktionsumfang nicht nötig, `QUEUE_CONNECTION=database` ist daher unkritisch.
- Aktivitätslog: Aufbewahrung laut Konfiguration 365 Tage, aber ohne Zeitplan. Entweder `activitylog:clean` über den Scheduler (`Schedule::command(...)->daily()` in `routes/console.php`) oder manuell betreiben.
- PDF-Erzeugung läuft im Request (DomPDF); Protokolle mit vielen Fotos erhöhen Speicher- und Laufzeitbedarf, da Bilder als Data-URI eingebettet werden.
- Datenmigration Kontakte: Vor `php artisan migrate` auf einer bestehenden Installation ein vollständiges Datenbank-Backup erstellen. Migration `2026_04_12_000006` ist nicht umkehrbar.
- Neue Migrationen vom 2026-10-23 (`add_soft_deletes_to_orders_table`, `create_number_sequences_table`): vor dem Deployment `php artisan migrate` ausführen. Die Zähler für Auftrags- und Kundennummern werden beim ersten Zugriff aus den vorhandenen Nummern abgeleitet, eine Datenübernahme ist nicht nötig. Gelöschte Aufträge bleiben als Soft Delete in der Tabelle `orders`.
- Health-Check: `GET /up`.
- Private Dateien (S1): Lieferscheine, Protokoll-PDFs, Unterschriften und Fotos liegen auf der Disk `local` (`storage/app/private`). Bei einer bestehenden Installation nach dem Deployment einmalig `php artisan files:make-private --dry-run` und danach `php artisan files:make-private` ausführen; ohne diesen Schritt liefern Protokoll-PDFs und Lieferscheine, die noch auf `public` liegen, 404, und der Mailanhang fehlt. Vorher ein Backup von `storage/app` anlegen. Der Befehl behält die Pfade bei, entfernt die öffentliche Kopie erst nach erfolgreichem Kopieren, fasst `avatars/` und `work-instructions/` nicht an und meldet Konflikte (gleicher Pfad, andere Größe) mit Fehlercode. `storage/app/private` gehört in die Datensicherung und darf nicht per Webserver freigegeben werden.
- Versionsverwaltung: Branch `dev` ist der einzige Branch und zugleich Hauptzweig; es gibt keine CI-Konfiguration im Repository. Datei `.claude/settings.local.json` ist versioniert, enthält lokale Pfade (`E:\00_Git\RMS`) und gehört in `.gitignore`.
