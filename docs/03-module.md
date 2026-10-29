# 03 Fachmodule

Jeder Abschnitt nennt Zweck, Einstiegspunkte (Controller, Routen-Präfix), die Fachregeln aus dem Code und bekannte Lücken. Verweise der Form K1, S1, M1 beziehen sich auf [08-bekannte-probleme.md](08-bekannte-probleme.md).

## Kontakte

Controller: `Contact\ContactController`, Präfix `/contacts`, Rechte `contacts.*`.

- Ein Kontakt ist Kunde, Mieter oder beides (`ContactType`). Er ersetzt die früheren Tabellen `customers` und `tenants`.
- Liste: Suche über Name, Kundennummer, E-Mail, Telefon, Ort; Filter nach Typ und Status; 15 je Seite; `withCount(['orders', 'rentals'])`.
- Anlegen und Ändern validieren Name (Pflicht), E-Mail, Telefon, Notizen. Adressfelder und Ansprechpartner werden nur bei Typ `customer` oder `both` mit validiert (alle optional).
- Beim Wechsel eines Mieters zu Kunde oder beides wird nachträglich eine Kundennummer vergeben.
- Löschen ist gesperrt, wenn Aufträge oder Vermietungen existieren; Aktiv/Inaktiv lässt sich per `toggle-status` umschalten. Inaktive Kunden erscheinen nicht mehr in der Auftragsanlage.
- Frühere Lücke K1 (Relationen `Contact::orders()` und `rentals()` ohne Imports) ist behoben; Liste, Detailseite und Löschschutz sind durch Tests abgedeckt.

## Auftragsverwaltung

### Aufträge (`Order\OrderController`, Präfix `/orders`)

- Anlegen (`orders.create`): Kunde wählen (`existing`, nur aktive Kunden) oder einen Einmalkunden erfassen (`individual`; es wird ein neuer `Contact` vom Typ `customer` angelegt). Lieferart `delivery` oder `pickup`, Lieferdatum (muss nach heute liegen), Positionen aus aktiven Artikeln mit Menge und Notiz. Bei `delivery` muss der Kunde Straße, PLZ und Ort haben, sonst wird abgelehnt.
- Positionen speichern einen Schnappschuss von Name, SKU und Preis des Artikels. Preisänderungen am Artikel wirken nicht auf bestehende Aufträge.
- Bearbeiten (`orders.edit`): nur in Status `new` und `in_progress`. Das Formular sendet `expected_status`; weicht der aktuelle Status ab, wird mit einer Meldung zurückgeleitet (einfache Konfliktprüfung). Gepackte oder teilgepackte Positionen können weder geändert noch entfernt werden. Das Lieferdatum darf beim Ändern auch heute sein (`after_or_equal`), beim Anlegen nicht (`after`).
- Löschen (`orders.delete`): nur in `new` und `in_progress`. Der Auftrag wird weich gelöscht (Soft Delete): Er verschwindet aus Listen, Detailseiten (404), Packübersicht und Auswertungen, bleibt aber mit Positionen und vollständiger Historie in der Datenbank. Der Observer schreibt das Ereignis `order_deleted`. Die Auftragsnummer bleibt belegt. Positionen gelöschter Aufträge lassen sich nicht mehr packen oder entpacken (404). Eine Wiederherstellung ist in der Oberfläche nicht vorgesehen (nur per `Order::withTrashed()->find(...)->restore()`).
- Statuswechsel (`orders.update-status`, `PATCH /orders/{order}/status`): nur erlaubte Übergänge laut `OrderStatus::canTransitionTo`; `packed` setzt voraus, dass alle Positionen gepackt sind; `delivered` setzt `delivered_at`. Es gibt keine getrennte Berechtigung je Übergang.
- Historie (`orders.view-history`): Ereignisse `order_created`, `status_changed`, `order_deleted`, `item_packed`, `item_unpacked`, `order_completed`, `delivery_note_generated`, `delivery_note_printed`. Erzeugt durch `OrderObserver` und `Order::logHistory()` inklusive Benutzer, IP und User-Agent.

### Artikel (`Order\ArticleController`, Präfix `/articles`)

CRUD mit Name, SKU (eindeutig), Beschreibung, Preis, Aktiv-Flag. Löschen (Soft Delete) ist gesperrt, solange Auftragspositionen auf den Artikel verweisen. Alle Artikelrouten sind mit `orders.create` geschützt, es gibt keine eigenen Artikelrechte.

### Packen (`Order\OrderPackingController`, Präfix `/packing`, Recht `orders.pack`)

1. Übersicht der Aufträge im Status `in_progress`, sortiert nach Lieferdatum, dann nach Packfortschritt (früherer Fehler K2, die Übersicht lud die entfernte Relation `customer`, ist behoben).
2. Detailseite je Auftrag (nur Status `in_progress`).
3. Positionen werden komplett oder in Teilmengen gepackt (`markAsPacked`); mehr als die Restmenge wird abgelehnt. "Entpacken" setzt die Position vollständig auf 0 zurück und ist nur bei bearbeitbarem Auftrag erlaubt.
4. "Abschließen" (`lockOrder`) verlangt, dass alle Positionen gepackt sind, setzt den Status `packed` und `packed_by`.
5. Packen, Entpacken und Abschließen sind nur im Status `in_progress` möglich (früherer Befund M1). Der Controller prüft das mit übersetzter Meldung (`packing.messages.order_not_packable`), zusätzlich sichern `OrderItem::markAsPacked`, `OrderItem::unpack` und `Order::lockOrder` die Regel im Model ab. Ein Abschluss kann dadurch keinen bereits gepackten, ausgelieferten oder zugestellten Auftrag mehr auf `packed` zurücksetzen.

### Lieferschein (`Order\DeliveryNoteController`, Präfix `/delivery-notes`, Recht `orders.print`)

- `generate`: nur für Status `packed`; erzeugt das PDF in deutscher Sprache und speichert es auf der privaten Disk (`local`), der Pfad steht am Auftrag.
- `print`: nur für Status `packed`, `in_delivery` und `delivered` (sonst Fehlermeldung `delivery_notes.messages.not_printable`, früherer Befund M2). Erzeugt das PDF bei Bedarf. Beim ersten Druck eines Auftrags im Status `packed` wird er auf `in_delivery` gesetzt (`delivery_note_printed_at`, Historieneintrag); `Order::markAsInDelivery` prüft dafür `canTransitionTo`. Ein erneuter Druck bei `in_delivery` oder `delivered` liefert nur den Download und ändert weder Status noch Druckzeitpunkt.
- `download`: liefert die vorhandene Datei von der privaten Disk. Vor jedem Zugriff wird per `realpath` geprüft, dass der Pfad innerhalb der Disk liegt. Der Zugriff erfordert Anmeldung und `orders.print`; eine direkte URL zur Datei gibt es nicht.
- Früherer Fehler K3 (Template las `$order->customer`) ist behoben; das Template liest `$order->contact`.

### Auswertungen (`Order\OrderAnalyticsController`, Präfix `/analytics`)

- Seite (`analytics.view`): Zeitraum 7, 14, 30, 60, 90, 180, 365 Tage oder alle. Kennzahlen: Aufträge gesamt, aktive Aufträge (`new`, `in_progress`), gepackte Positionen; dazu Aufträge nach Status, Verlauf pro Tag, Top-10-Artikel und Packleistung je Mitarbeiter.
- JSON-Endpunkte `/analytics/api/*` (60 Anfragen/Minute) sind vorhanden, werden aber von keiner View genutzt.
- PDF-Export (`analytics.export`, 10 pro Minute): Der Report berücksichtigt den gewählten Zeitraum (7, 14, 30, 60, 90, 180, 365 Tage oder `all`, Standard 30; früherer Befund M4) für Gesamtzahl, Aufträge nach Status, Top-Artikel und Packleistung und nennt ihn im Kopf ("Zeitraum: ..."). Vorher wurde der Parameter ignoriert, und "alle Zeit" scheiterte schon an der Validierung, obwohl der Export-Link es übergibt.
- Die Zeitreihen-Abfrage nutzt `DATE_FORMAT` (MySQL/MariaDB). Sie läuft nicht unter SQLite, was Feature-Tests der Auswertung auf der Test-Datenbank verhindert.

## Vermietung

### Stammdaten

- Hallen (`Rental\HallController`, `/halls`): Name, Adresse, Notizen und eine Inventarliste mit Soll-Menge je Position (Pivot `hall_inventory`). Löschen ist gesperrt, solange Vermietungen existieren.
- Inventar (`Rental\InventoryItemController`, `/inventory-items`): Name, SKU, Beschreibung. Löschen ist gesperrt, solange die Position einer Halle zugeordnet ist. Der frühere Fehler K4 (Controller-Parameter `$inventory_item` passte nicht zur Route `{inventoryItem}`) ist behoben.

### Vermietungen (`Rental\RentalController`, `/rentals`)

- Pflichtfelder: Mieter (Kontakt vom Typ `tenant` oder `both`), Halle, Beginn, Ende (nach Beginn); optional Preis und Kaution; Status `scheduled`, `active`, `closed`, `cancelled`.
- Überschneidungsprüfung je Halle (außer Status `cancelled`, früherer Befund M9): Die Zeiträume sind halboffen (`Rental::scopeOverlapping`: bestehender Beginn < neues Ende und bestehendes Ende > neuer Beginn). Endet eine Vermietung genau, wenn die nächste beginnt, ist das erlaubt. Die Prüfung steht an einer Stelle (`RentalController::hallIsBooked`) und gilt für Anlegen und Ändern; beim Ändern wird die Vermietung selbst ausgenommen. Die Eingaben werden zu Carbon geparst, damit Formulareingaben wie `2026-11-10T10:00` auf jeder Datenbank gleich verglichen werden.
- Löschen ist gesperrt, sobald Protokolle existieren.
- Aktionen `handover` und `return` öffnen das zugehörige Protokoll oder legen es an. Beim Übergabeprotokoll werden die Positionen aus dem Hallen-Inventar übernommen (Beschriftung `Name (Soll: n)`). Das Rückgabeprotokoll kopiert Checkliste und Positionen des Übergabeprotokolls (legt dieses an, falls es fehlt). Je Vermietung gibt es maximal ein Protokoll je Typ (Unique-Index).

### Protokolle (`Rental\ProtocolController`, `/protocols`)

1. Formular (`protocols.create`): Zählerstände Strom und Wasser, Notizen, je Position Zustand (`ok`, `missing`, `damaged`), Kommentar und Aufschlag, Fotos.
2. Speichern (`protocols.edit`): in einer Transaktion; Fotos nur JPEG/PNG, höchstens 5 MB je Foto, 20 je Upload und 30 je Protokoll.
3. Unterschreiben (`protocols.sign`): Rolle `tenant` oder `landlord`, Name und Unterschrift als PNG-Data-URI. Geprüft werden Format (echtes PNG über `getimagesizefromstring`) und Größe (max. 2 MB). Die Zeile des Protokolls wird mit `lockForUpdate` gesperrt, damit nicht doppelt finalisiert wird.
4. Sobald beide Rollen unterschrieben haben: PDF erzeugen und speichern, SHA-256 und Metadaten (IP, User-Agent) im Protokoll ablegen, Vermietungsstatus fortschreiben (Übergabe: `scheduled` zu `active`; Rückgabe: zu `closed`), nach dem Commit E-Mail mit PDF-Anhang an den Mieter senden (synchron, Fehler werden nur geloggt).
5. PDF anzeigen (`protocols.pdf`): `response()->file()`; nur wenn finalisiert.
6. Der frühere Fehler K5 (Liste, Formular, PDF und E-Mail verwendeten die entfernte Relation `rental.tenant`) ist behoben; verwendet wird `rental.contact`.

## Arbeitsanweisungen

- Verwaltung (`Admin\WorkInstructionCategoryController`, `Admin\WorkInstructionController`, Präfix `/admin/work-instructions`, Recht `work-instructions.manage`): Kategorien ("Tätigkeiten") mit Reihenfolge und Aktiv-Flag; je Kategorie PDF-Anweisungen (max. 20 MB, nur PDF), gespeichert auf der privaten Disk (`local`). Kategorien mit Anweisungen lassen sich nicht löschen. Beim Ersetzen oder Löschen wird die alte Datei entfernt.
- Öffentlich (`WorkInstructionPublicController`): `/anweisungen/{slug}` listet aktive Anweisungen, `/anweisungen/{slug}/{id}` liefert das PDF inline. Keine Anmeldung nötig, inaktive Einträge (Kategorie oder Anweisung), falsche Kategorie-Zuordnung und fehlende Dateien liefern 404. Der Zweck ist offenbar der Aushang per QR-Code oder Link; die Inhalte sind damit für jeden mit der URL lesbar, solange sie aktiv sind. Die PDFs liegen auf der privaten Disk und sind nur über diese Route erreichbar (früherer Rest-Risiko-Punkt aus S1: deaktivierte Anweisungen waren über die `/storage`-URL abrufbar).

## Administration

- Benutzer (`Admin\UserController`, `/admin/users`, `user.*`): Anlegen mit Passwort (min. 8 Zeichen) sowie Rollen und Direktrechten; Ändern mit optionalem neuen Passwort. Schutz: Der Benutzer kann sich nicht selbst löschen, und ein `super-admin` kann sich die eigene Rolle nicht entziehen. Anlegen, Ändern, Löschen sowie Aktivieren und Deaktivieren schreiben manuelle Einträge ins Aktivitätslog (Log `admin`, Ereignisse `user.created`, `user.updated`, `user.deleted`, `user.activated`, `user.deactivated`). Das Löschen eines Benutzers entfernt dessen Aufträge nicht mehr (früherer Befund S3): `orders.created_by`, `orders.packed_by`, `order_items.packed_by` und `order_history.user_id` werden leer; die Aufträge bleiben mit Positionen und Historie erhalten, in der Oberfläche steht "Gelöschter Benutzer". Wer einen Auftrag angelegt hat, lässt sich nach dem Löschen nicht mehr feststellen. Deshalb ist für ausgeschiedene Mitarbeiter das **Deaktivieren** der empfohlene Weg: In der Benutzerliste schaltet der Button "Deaktivieren"/"Aktivieren" (Recht `user.edit`, Route `PATCH /admin/users/{user}/toggle-status`) das Konto um. Ein deaktiviertes Konto kann sich nicht anmelden (nach korrektem Passwort erscheint die Meldung "Dieses Konto wurde deaktiviert", bei falschem Passwort die allgemeine Fehlermeldung, damit niemand erfährt, ob ein Konto existiert), eine laufende Sitzung endet beim nächsten Seitenaufruf (`EnsureUserIsActive`), und das Token für "Angemeldet bleiben" wird gelöscht. Aufträge, Positionen und Historie bleiben vollständig und zeigen weiter den Namen des Erstellers. Schutz: Man kann sich nicht selbst deaktivieren (403), und der letzte aktive `super-admin` kann nicht deaktiviert werden. Rollen und Rechte eines deaktivierten Benutzers bleiben bestehen und gelten nach dem Aktivieren wieder. Im Admin-Formular angelegte Benutzer gelten als verifiziert (`email_verified_at` wird beim Anlegen gesetzt, früherer Befund M5) und können sich sofort anmelden. Ändert ein Administrator später die E-Mail-Adresse, bleibt die Verifizierung bestehen. Ändert ein Benutzer seine eigene Adresse im Profil, wird die Verifizierung zurückgesetzt (Breeze-Standard) und der Benutzer muss die neue Adresse über `/verify-email` bestätigen; dafür muss der Mailversand funktionieren.
- Rollen (`Admin\RoleController`, `role.*`): Name und Rechte; die Rolle `super-admin` kann nicht gelöscht werden.
- Berechtigungen (`Admin\PermissionController`, `perm.*`): freie Verwaltung der Rechtenamen. Menüpunkt ist ausgeblendet.
- Aktivitätslog (`Admin\ActivityLogController`, `/admin/activity-log`, Recht `user.list`): Suche, Filter nach Log-Name und Verursacher, 50 je Seite. Geloggt werden Änderungen an Name und E-Mail von `User` (nur geänderte Felder) sowie die manuellen Benutzereinträge. Aufträge nutzen weiterhin die eigene Tabelle `order_history`.
- Nach Rechteänderungen wird der Spatie-Cache zurückgesetzt (`permission:cache-reset` und `forgetCachedPermissions`, doppelt vorhanden).

## Dashboard und Profil

- Dashboard (`/`): lädt nur mit Recht `rentals.view` die heutigen Vermietungen (Beginn oder Ende heute, höchstens 10) und nur mit `protocols.view` die offenen Protokolle (ohne PDF, höchstens 10).
- Profil (`/profile`): Name und E-Mail ändern, Passwort ändern, Konto löschen. Avatar-Upload wird mit Intervention Image als WebP unter `avatars/{user_id}/` gespeichert; ohne Avatar wird `images/avatar-placeholder.png` verwendet.

## Mehrsprachigkeit im Betrieb

Umschalten über das Sprachmenü im Header (`/language/{locale}`, Session). Lieferscheine werden unabhängig davon immer deutsch erzeugt.
