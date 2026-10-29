# RMS - Rosenau Management System

Stand der Dokumentation: 2026-10-23, Branch `dev`, Commit `6787310`.

RMS ist eine interne Laravel-Webanwendung (Laravel 12, PHP 8.2+, MariaDB) mit vier Fachbereichen:

| Bereich | Zweck |
|---|---|
| Kontakte | Gemeinsamer Stamm für Kunden und Mieter (`Contact`) |
| Auftragsverwaltung | Aufträge, Artikel, Packen, Lieferschein (PDF), Auswertungen |
| Vermietung | Hallen, Inventar, Vermietungen, Übergabe-/Rückgabeprotokolle mit Unterschrift, Fotos und PDF |
| Arbeitsanweisungen | PDF-Ablage je Tätigkeit, öffentlich abrufbar unter `/anweisungen/{slug}` |

Dazu kommen Benutzer-, Rollen- und Rechteverwaltung (Spatie Permission), ein Aktivitätslog (Spatie Activitylog) sowie eine zweisprachige Oberfläche (Deutsch, Englisch).

## Inhaltsverzeichnis

| Datei | Inhalt |
|---|---|
| [01-architektur.md](01-architektur.md) | Stack, Verzeichnisstruktur, Request-Ablauf, Konfiguration, Dateispeicher, PDF, Mehrsprachigkeit, Frontend |
| [02-datenmodell.md](02-datenmodell.md) | Tabellen, Beziehungen (ER-Diagramm), Enums, Nummernkreise, Migrationshistorie |
| [03-module.md](03-module.md) | Fachlogik und Abläufe je Modul |
| [04-rechte-und-rollen.md](04-rechte-und-rollen.md) | Permission-Katalog, Rollen, Seeder, Zuordnung zu Routen |
| [05-routen.md](05-routen.md) | Alle Routen mit Controller und Berechtigung |
| [06-entwicklung-und-betrieb.md](06-entwicklung-und-betrieb.md) | Setup, Befehle, Tests, Umgebungsvariablen, Betriebshinweise |
| [07-coding-guidelines.md](07-coding-guidelines.md) | Verbindliche Konventionen und Abweichungen im Bestand |
| [08-bekannte-probleme.md](08-bekannte-probleme.md) | Geprüfte Fehler, Risiken und technische Schulden mit Priorität |

## Wichtigste Erkenntnisse vorab

1. Der Umbau vom 2026-04-12 (Zusammenführung von `tenants` und `customers` zu `contacts`) war nicht vollständig nachgezogen. Die dadurch verursachten Fehler K1 bis K5 sowie M6 (Seeder, Rechte) sind behoben. Ebenfalls behoben und getestet: K6 (Auftrag löschen, jetzt Soft Delete), K7 (atomare Nummernvergabe), M1 (Packen nur in Bearbeitung), M2 (Lieferscheindruck erst ab "verpackt") und M5 (Admin-Benutzer sind verifiziert). Offen sind die übrigen Punkte in [08-bekannte-probleme.md](08-bekannte-probleme.md).
2. Automatisierte Tests gibt es für Kontakte, Packübersicht, Lieferschein, Inventar, Protokolle, Seeder, Auftragslöschung, Packstatus, Nummernkreise, Benutzerverwaltung, Benutzer löschen, private Dateispeicherung und Frontend-Konsistenz (100 Tests, gesamt 122 grün). Auftragsanlage und -bearbeitung, Vermietungen und Auswertung sind weiterhin ungetestet.
3. Lieferscheine, Protokoll-PDFs, Unterschriften und Fotos liegen auf der privaten Disk und werden nur über berechtigte Routen ausgeliefert (S1 behoben). Auf bestehenden Installationen muss einmalig `php artisan files:make-private` ausgeführt werden. Das Löschen eines Benutzers entfernt dessen Aufträge nicht mehr (S3 behoben).
4. Der Code-Stil ist uneinheitlich: Das Laravel-Pint-Standardpreset beanstandet 54 von 118 PHP-Dateien.

## Wie diese Dokumentation entstanden ist

Grundlage ist der Quellcode im Repository (Routen, Models, Controller, Migrationen, Seeder, Views, Sprachdateien, Konfiguration). Folgende Aussagen wurden zusätzlich per Skript gegen die laufende Anwendung geprüft: Routenliste (`php artisan route:list`), fehlende Klassen/Relationen, Route-Model-Binding bei Inventar-Positionen, Pint-Stil. Der Testlauf wurde nach `composer install` ausgeführt (siehe [06-entwicklung-und-betrieb.md](06-entwicklung-und-betrieb.md)). Die Datenbank wurde nicht angesprochen; Aussagen zum Laufzeitverhalten mit Daten sind aus dem Code abgeleitet.
