# CLAUDE.md - RMS (Rosenau Management System)

Laravel 12 / PHP 8.2+ / MariaDB. Projektdokumentation und verbindliche Konventionen liegen in `docs/` (Einstieg: `docs/README.md`).

## Dokumentation ist Pflicht bei jeder Änderung

Bei jeder Änderung wird die Dokumentation in `docs/` im selben Zug mit aktualisiert, sofern sie betroffen ist. Welche Datei bei welcher Änderung zu prüfen ist, steht in `docs/07-coding-guidelines.md`, Abschnitt 9. Kurzfassung:

| Änderung | Dokument |
|---|---|
| Tabellen, Spalten, Enums, Nummernkreise, Migrationen | `docs/02-datenmodell.md` |
| Fachregeln, Abläufe, Controller, Models, Mail, PDF | `docs/03-module.md` (ggf. `docs/01-architektur.md`) |
| Rechte, Rollen, Seeder | `docs/04-rechte-und-rollen.md` |
| Routen | `docs/05-routen.md` (aus `php artisan route:list --except-vendor --json` neu erzeugen) |
| Setup, Befehle, Teststand, Betrieb | `docs/06-entwicklung-und-betrieb.md` |
| Konventionen | `docs/07-coding-guidelines.md` |
| Befund behoben oder neu entdeckt | `docs/08-bekannte-probleme.md` (Status, Prioritätstabelle, Reihenfolge) |
| Eckdaten wie Teststand | `docs/README.md` |

Ist keine Doku betroffen, das am Ende der Arbeit kurz nennen. Doku-Dateien nach jeder Ersetzung per Sichtprüfung kontrollieren (Umlaute und Backticks dürfen nicht verloren gehen).

## Weitere Regeln

- Umlaute (ä ö ü Ä Ö Ü ß) immer erhalten, Dateien sind UTF-8.
- Kommentare im Code: nur ASCII plus Umlaute, keine Emojis, keine Gedankenstriche (stattdessen `-`).
- Schreibgeschützte Dateien (TFS) melden und auf manuelles Auschecken warten; Schreibschutz nie selbst entfernen.
- Geheimnisse und personenbezogene Daten gehören weder in Code, Tests noch Doku. Testdaten sind erfunden (E-Mail-Domains `.example` oder `.test`).
- Neue Routen brauchen ein `permission:`-Recht; neue Rechte per Migration anlegen und im passenden Seeder Rollen zuordnen.
- Texte immer über `lang/de` und `lang/en` (identische Schlüssel).

## Befehle

```bash
composer test                      # Pest-Tests (SQLite in-memory)
vendor/bin/pint --test <Dateien>   # Stilprüfung, nur geänderte Dateien
php artisan route:list --except-vendor
```

Unter Windows steht `php` nur in PowerShell/cmd zur Verfügung (Herd), nicht im Git-Bash-Pfad. Stand der Tests und bekannte Probleme: `docs/06-entwicklung-und-betrieb.md`, `docs/08-bekannte-probleme.md`.
