# 04 Rechte und Rollen

Berechtigungen laufen über `spatie/laravel-permission` (Guard `web`, Cache 24 Stunden, keine Teams). Geprüft wird an drei Stellen:

1. Route: `->middleware('permission:<name>')` (maßgeblich, siehe [05-routen.md](05-routen.md))
2. View: `@can('<name>')`, `@canany([...])` für Navigation und Schaltflächen
3. Code: `auth()->user()->can('<name>')` (Dashboard)

Die Rolle `super-admin` besteht jede Prüfung (`Gate::before` in `AppServiceProvider`), unabhängig von zugewiesenen Rechten.

## Permission-Katalog

Namensschema: `<bereich>.<aktion>`. Ausnahme: Die Administration nutzt `user.`, `role.`, `perm.` mit den Aktionen `list`, `add`, `edit`, `delete`.

| Bereich | Rechte | Verwendet in |
|---|---|---|
| Benutzer | `user.list`, `user.add`, `user.edit`, `user.delete` | `/admin/users`; `user.edit` gilt auch für Aktivieren und Deaktivieren (`users.toggle-status`); `user.list` schützt zusätzlich das Aktivitätslog |
| Rollen | `role.list`, `role.add`, `role.edit`, `role.delete` | `/admin/roles` |
| Berechtigungen | `perm.list`, `perm.add`, `perm.edit`, `perm.delete` | `/admin/permissions`; Form Requests prüfen zusätzlich `perm.edit` bzw. `role.edit` |
| Kontakte | `contacts.view`, `contacts.create`, `contacts.edit`, `contacts.delete`, `contacts.toggle-status` | `/contacts` |
| Aufträge | `orders.view`, `orders.create`, `orders.edit`, `orders.delete`, `orders.update-status`, `orders.view-history`, `orders.pack`, `orders.print` | `/orders`, `/packing`, `/delivery-notes`; `orders.create` schützt auch `/articles` |
| Auswertung | `analytics.view`, `analytics.export` | `/analytics` |
| Inventar | `inventory.view`, `inventory.create`, `inventory.edit`, `inventory.delete` | `/inventory-items` |
| Hallen | `halls.view`, `halls.create`, `halls.edit`, `halls.delete` | `/halls` |
| Vermietungen | `rentals.view`, `rentals.create`, `rentals.edit`, `rentals.delete`, `rentals.handover`, `rentals.return` | `/rentals` |
| Protokolle | `protocols.view`, `protocols.create`, `protocols.edit`, `protocols.sign`, `protocols.pdf` | `/protocols` |
| Arbeitsanweisungen | `work-instructions.manage` | `/admin/work-instructions` |

Das früher im `RolesAndPermissionsSeeder` angelegte, aber nirgends geprüfte Recht `app.settings` wird nicht mehr angelegt. Auf bestehenden Installationen kann der Eintrag in der Tabelle `permissions` noch stehen; er ist wirkungslos und lässt sich in der Rechteverwaltung löschen.

Die früheren Rechte `tenants.*` und `customers.*` werden von den Seedern nicht mehr angelegt. Auf bestehenden Installationen können sie noch in der Tabelle `permissions` stehen (Migration `000007` kopiert nur die Rollenzuordnung, löscht aber nichts); sie haben keine Wirkung und können über die Rechteverwaltung entfernt werden.

## Verschachtelte Rechte

Mehrere Routengruppen verlangen ein Basisrecht plus ein Aktionsrecht. Wer nur das Aktionsrecht hat, kommt nicht durch:

| Gruppe | Basisrecht | Beispiel |
|---|---|---|
| `/orders` | `orders.view` | Bearbeiten braucht `orders.view` und `orders.edit` |
| `/analytics` | `analytics.view` | Export braucht `analytics.view` und `analytics.export` |
| `/rentals` | `rentals.view` | Übergabe braucht `rentals.view` und `rentals.handover` |
| `/halls` | `halls.view` | |
| `/inventory-items` | `inventory.view` | |
| `/protocols` | `protocols.view` | Das Protokollformular selbst verlangt `protocols.create`, nicht `protocols.view` |
| `/packing` | `orders.pack` | kein weiteres Basisrecht |
| `/delivery-notes` | `orders.print` | kein weiteres Basisrecht |

## Rollen laut Seedern

| Rolle | Rechte | Seeder |
|---|---|---|
| `super-admin` | alle (zusätzlich `Gate::before`) | `RolesAndPermissionsSeeder` (setzt alle zum Seed-Zeitpunkt vorhandenen Rechte; weist die Rolle `User::find(1)` zu) |
| `packer` | `orders.view`, `orders.pack`, `orders.update-status` | `OrderPermissionsSeeder` |
| `order-manager` | `orders.view/create/edit/delete/update-status/print/view-history`, `analytics.view/export`; zusätzlich `contacts.view/create/edit/delete/toggle-status` | `OrderPermissionsSeeder`, `ContactPermissionsSeeder` |
| `rental-manager` | `inventory.*`, `contacts.view/create/edit/delete`, `halls.*`, `rentals.*`, `protocols.*` | `RentalPermissionsSeeder` |
| `rental-staff` | `inventory.view`, `contacts.view/create/edit`, `halls.view`, `rentals.view/create/edit/handover/return`, `protocols.view/create/sign/pdf` | `RentalPermissionsSeeder` |
| `inventory-manager` | `inventory.*` | `RentalPermissionsSeeder` |

Die Rollenpflege im laufenden System erfolgt über die Rollenverwaltung; die Seeder dienen nur der Erstausstattung.

## Auswirkungen des Kontaktumbaus auf die Rechte

- Migration `2026_04_12_000007_migrate_contact_permissions` legt `contacts.*` an und kopiert die Rollenzuordnung von `tenants.*` und `customers.*`. Das funktioniert nur, wenn die alten Rechte zum Zeitpunkt der Migration schon in der Datenbank stehen (bestehende Installation).
- Auf einer frischen Installation laufen die Migrationen vor den Seedern. Befund M6 (Seeder legten nur `tenants.*` und `customers.*` an, `contacts.*` gehörte keiner Rolle) ist behoben: `RentalPermissionsSeeder` vergibt `contacts.*` an `rental-manager` und `rental-staff`, der neue `ContactPermissionsSeeder` (früher `CustomerPermissionsSeeder`) an `order-manager`. Abgesichert durch `tests/Feature/Database/PermissionSeedersTest.php`.
- `TestCustomerSeeder` heißt jetzt `TestContactSeeder` und legt drei Kontakte über das Model `Contact` an (erfundene Daten, Domains `.example`).

## Empfehlungen

- Eigene Artikelrechte (`articles.*`) einführen, statt `orders.create` zu überladen.
- `protocols.create` für das reine Anzeigen des Protokollformulars durch `protocols.view` ersetzen oder bewusst dokumentieren.
