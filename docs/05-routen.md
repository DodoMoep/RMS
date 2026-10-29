# 05 Routen

Die Tabellen wurden aus `php artisan route:list --except-vendor --json` erzeugt (Stand Commit `6787310`). Die Spalte "Recht" zeigt alle `permission:`-Middlewares der Route: Bei verschachtelten Gruppen sind das das Basisrecht der Gruppe und das Aktionsrecht, beide müssen erfüllt sein (z. B. `orders.view, orders.edit`). Die Rolle `super-admin` besteht jede Prüfung über `Gate::before`.

Zugriffsstufen:

- `Login+verifiziert`: Middleware `auth` und `verified` (E-Mail muss bestätigt sein)
- `Login`: nur `auth`
- `oeffentlich`: ohne Anmeldung erreichbar. Das sind die Arbeitsanweisungen, der Sprachwechsel und die Gast-Routen von Breeze (`/login`, `/forgot-password`, `/reset-password`, die nur für nicht angemeldete Benutzer gedacht sind)

Eine Registrierungsroute gibt es nicht. `Auth\RegisteredUserController` ist nicht eingebunden; `tests/Feature/Auth/RegistrationDisabledTest.php` hält fest, dass `/register` mit 404 antwortet und sich niemand selbst registrieren kann. Der Health-Check liegt unter `/up`.

Zusätzlich zur Routen-Berechtigung blenden die Views Menüpunkte und Schaltflächen per `@can` aus.

### /(root)

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| GET | `/` | `dashboard` | `DashboardController@index` | - | Login+verifiziert |

### /admin

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| GET | `/admin/activity-log` | `activity-log.index` | `Admin\ActivityLogController@index` | `user.list` | Login+verifiziert |
| GET | `/admin/permissions` | `permissions.index` | `Admin\PermissionController@index` | `perm.list` | Login+verifiziert |
| POST | `/admin/permissions` | `permissions.store` | `Admin\PermissionController@store` | `perm.add` | Login+verifiziert |
| GET | `/admin/permissions/create` | `permissions.create` | `Admin\PermissionController@create` | `perm.add` | Login+verifiziert |
| PUT | `/admin/permissions/{permission}` | `permissions.update` | `Admin\PermissionController@update` | `perm.edit` | Login+verifiziert |
| DELETE | `/admin/permissions/{permission}` | `permissions.destroy` | `Admin\PermissionController@destroy` | `perm.delete` | Login+verifiziert |
| GET | `/admin/permissions/{permission}/edit` | `permissions.edit` | `Admin\PermissionController@edit` | `perm.edit` | Login+verifiziert |
| GET | `/admin/roles` | `roles.index` | `Admin\RoleController@index` | `role.list` | Login+verifiziert |
| POST | `/admin/roles` | `roles.store` | `Admin\RoleController@store` | `role.add` | Login+verifiziert |
| GET | `/admin/roles/create` | `roles.create` | `Admin\RoleController@create` | `role.add` | Login+verifiziert |
| PUT | `/admin/roles/{role}` | `roles.update` | `Admin\RoleController@update` | `role.edit` | Login+verifiziert |
| DELETE | `/admin/roles/{role}` | `roles.destroy` | `Admin\RoleController@destroy` | `role.delete` | Login+verifiziert |
| GET | `/admin/roles/{role}/edit` | `roles.edit` | `Admin\RoleController@edit` | `role.edit` | Login+verifiziert |
| GET | `/admin/users` | `users.index` | `Admin\UserController@index` | `user.list` | Login+verifiziert |
| POST | `/admin/users` | `users.store` | `Admin\UserController@store` | `user.add` | Login+verifiziert |
| GET | `/admin/users/create` | `users.create` | `Admin\UserController@create` | `user.add` | Login+verifiziert |
| PUT | `/admin/users/{user}` | `users.update` | `Admin\UserController@update` | `user.edit` | Login+verifiziert |
| DELETE | `/admin/users/{user}` | `users.destroy` | `Admin\UserController@destroy` | `user.delete` | Login+verifiziert |
| GET | `/admin/users/{user}/edit` | `users.edit` | `Admin\UserController@edit` | `user.edit` | Login+verifiziert |
| GET | `/admin/work-instructions` | `work-instructions.categories.index` | `Admin\WorkInstructionCategoryController@index` | `work-instructions.manage` | Login+verifiziert |
| POST | `/admin/work-instructions` | `work-instructions.categories.store` | `Admin\WorkInstructionCategoryController@store` | `work-instructions.manage` | Login+verifiziert |
| GET | `/admin/work-instructions/create` | `work-instructions.categories.create` | `Admin\WorkInstructionCategoryController@create` | `work-instructions.manage` | Login+verifiziert |
| PUT | `/admin/work-instructions/{category}` | `work-instructions.categories.update` | `Admin\WorkInstructionCategoryController@update` | `work-instructions.manage` | Login+verifiziert |
| DELETE | `/admin/work-instructions/{category}` | `work-instructions.categories.destroy` | `Admin\WorkInstructionCategoryController@destroy` | `work-instructions.manage` | Login+verifiziert |
| GET | `/admin/work-instructions/{category}/edit` | `work-instructions.categories.edit` | `Admin\WorkInstructionCategoryController@edit` | `work-instructions.manage` | Login+verifiziert |
| POST | `/admin/work-instructions/{category}/instructions` | `work-instructions.instructions.store` | `Admin\WorkInstructionController@store` | `work-instructions.manage` | Login+verifiziert |
| GET | `/admin/work-instructions/{category}/instructions/create` | `work-instructions.instructions.create` | `Admin\WorkInstructionController@create` | `work-instructions.manage` | Login+verifiziert |
| PUT | `/admin/work-instructions/{category}/instructions/{instruction}` | `work-instructions.instructions.update` | `Admin\WorkInstructionController@update` | `work-instructions.manage` | Login+verifiziert |
| DELETE | `/admin/work-instructions/{category}/instructions/{instruction}` | `work-instructions.instructions.destroy` | `Admin\WorkInstructionController@destroy` | `work-instructions.manage` | Login+verifiziert |
| GET | `/admin/work-instructions/{category}/instructions/{instruction}/edit` | `work-instructions.instructions.edit` | `Admin\WorkInstructionController@edit` | `work-instructions.manage` | Login+verifiziert |

### /analytics

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| GET | `/analytics` | `analytics.index` | `Order\OrderAnalyticsController@index` | `analytics.view` | Login+verifiziert |
| GET | `/analytics/api/orders-by-status` | `analytics.api.status` | `Order\OrderAnalyticsController@ordersByStatus` | `analytics.view` (Throttle 60,1) | Login+verifiziert |
| GET | `/analytics/api/orders-over-time` | `analytics.api.timeline` | `Order\OrderAnalyticsController@ordersOverTime` | `analytics.view` (Throttle 60,1) | Login+verifiziert |
| GET | `/analytics/api/packer-performance` | `analytics.api.packers` | `Order\OrderAnalyticsController@packerPerformance` | `analytics.view` (Throttle 60,1) | Login+verifiziert |
| GET | `/analytics/api/top-items` | `analytics.api.items` | `Order\OrderAnalyticsController@topItems` | `analytics.view` (Throttle 60,1) | Login+verifiziert |
| GET | `/analytics/export` | `analytics.export` | `Order\OrderAnalyticsController@exportReport` | `analytics.view, analytics.export` (Throttle 10,1) | Login+verifiziert |

### /anweisungen

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| GET | `/anweisungen/{category}` | `work-instructions.show` | `WorkInstructionPublicController@show` | - | oeffentlich |
| GET | `/anweisungen/{category}/{instruction}` | `work-instructions.serve` | `WorkInstructionPublicController@serve` | - | oeffentlich |

### /articles

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| GET | `/articles` | `articles.index` | `Order\ArticleController@index` | `orders.create` | Login+verifiziert |
| POST | `/articles` | `articles.store` | `Order\ArticleController@store` | `orders.create` | Login+verifiziert |
| GET | `/articles/create` | `articles.create` | `Order\ArticleController@create` | `orders.create` | Login+verifiziert |
| PUT | `/articles/{article}` | `articles.update` | `Order\ArticleController@update` | `orders.create` | Login+verifiziert |
| DELETE | `/articles/{article}` | `articles.destroy` | `Order\ArticleController@destroy` | `orders.create` | Login+verifiziert |
| GET | `/articles/{article}/edit` | `articles.edit` | `Order\ArticleController@edit` | `orders.create` | Login+verifiziert |

### /confirm-password

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| GET | `/confirm-password` | `password.confirm` | `Auth\ConfirmablePasswordController@show` | - | Login |
| POST | `/confirm-password` | `` | `Auth\ConfirmablePasswordController@store` | - | Login |

### /contacts

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| GET | `/contacts` | `contacts.index` | `Contact\ContactController@index` | `contacts.view` | Login+verifiziert |
| POST | `/contacts` | `contacts.store` | `Contact\ContactController@store` | `contacts.create` | Login+verifiziert |
| GET | `/contacts/create` | `contacts.create` | `Contact\ContactController@create` | `contacts.create` | Login+verifiziert |
| GET | `/contacts/{contact}` | `contacts.show` | `Contact\ContactController@show` | `contacts.view` | Login+verifiziert |
| PUT | `/contacts/{contact}` | `contacts.update` | `Contact\ContactController@update` | `contacts.edit` | Login+verifiziert |
| DELETE | `/contacts/{contact}` | `contacts.destroy` | `Contact\ContactController@destroy` | `contacts.delete` | Login+verifiziert |
| GET | `/contacts/{contact}/edit` | `contacts.edit` | `Contact\ContactController@edit` | `contacts.edit` | Login+verifiziert |
| PATCH | `/contacts/{contact}/toggle-status` | `contacts.toggle-status` | `Contact\ContactController@toggleStatus` | `contacts.toggle-status` | Login+verifiziert |

### /delivery-notes

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| GET | `/delivery-notes/{order}/download` | `delivery-notes.download` | `Order\DeliveryNoteController@download` | `orders.print` | Login+verifiziert |
| POST | `/delivery-notes/{order}/generate` | `delivery-notes.generate` | `Order\DeliveryNoteController@generate` | `orders.print` | Login+verifiziert |
| POST | `/delivery-notes/{order}/print` | `delivery-notes.print` | `Order\DeliveryNoteController@print` | `orders.print` | Login+verifiziert |

### /email

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| POST | `/email/verification-notification` | `verification.send` | `Auth\EmailVerificationNotificationController@store` | - (Throttle 6,1) | Login |

### /forgot-password

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| GET | `/forgot-password` | `password.request` | `Auth\PasswordResetLinkController@create` | - | Login |
| POST | `/forgot-password` | `password.email` | `Auth\PasswordResetLinkController@store` | - | Login |

### /halls

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| GET | `/halls` | `halls.index` | `Rental\HallController@index` | `halls.view` | Login+verifiziert |
| POST | `/halls` | `halls.store` | `Rental\HallController@store` | `halls.view, halls.create` | Login+verifiziert |
| GET | `/halls/create` | `halls.create` | `Rental\HallController@create` | `halls.view, halls.create` | Login+verifiziert |
| PUT | `/halls/{hall}` | `halls.update` | `Rental\HallController@update` | `halls.view, halls.edit` | Login+verifiziert |
| DELETE | `/halls/{hall}` | `halls.destroy` | `Rental\HallController@destroy` | `halls.view, halls.delete` | Login+verifiziert |
| GET | `/halls/{hall}/edit` | `halls.edit` | `Rental\HallController@edit` | `halls.view, halls.edit` | Login+verifiziert |

### /inventory-items

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| GET | `/inventory-items` | `inventory-items.index` | `Rental\InventoryItemController@index` | `inventory.view` | Login+verifiziert |
| POST | `/inventory-items` | `inventory-items.store` | `Rental\InventoryItemController@store` | `inventory.view, inventory.create` | Login+verifiziert |
| GET | `/inventory-items/create` | `inventory-items.create` | `Rental\InventoryItemController@create` | `inventory.view, inventory.create` | Login+verifiziert |
| PUT | `/inventory-items/{inventoryItem}` | `inventory-items.update` | `Rental\InventoryItemController@update` | `inventory.view, inventory.edit` | Login+verifiziert |
| DELETE | `/inventory-items/{inventoryItem}` | `inventory-items.destroy` | `Rental\InventoryItemController@destroy` | `inventory.view, inventory.delete` | Login+verifiziert |
| GET | `/inventory-items/{inventoryItem}/edit` | `inventory-items.edit` | `Rental\InventoryItemController@edit` | `inventory.view, inventory.edit` | Login+verifiziert |

### /language

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| GET | `/language/{locale}` | `language.switch` | `LanguageController@switch` | - | oeffentlich |

### /login

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| GET | `/login` | `login` | `Auth\AuthenticatedSessionController@create` | - | Login |
| POST | `/login` | `` | `Auth\AuthenticatedSessionController@store` | - | Login |

### /logout

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| POST | `/logout` | `logout` | `Auth\AuthenticatedSessionController@destroy` | - | Login |

### /orders

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| GET | `/orders` | `orders.index` | `Order\OrderController@index` | `orders.view` | Login+verifiziert |
| POST | `/orders` | `orders.store` | `Order\OrderController@store` | `orders.view, orders.create` | Login+verifiziert |
| GET | `/orders/create` | `orders.create` | `Order\OrderController@create` | `orders.view, orders.create` | Login+verifiziert |
| GET | `/orders/{order}` | `orders.show` | `Order\OrderController@show` | `orders.view` | Login+verifiziert |
| PUT | `/orders/{order}` | `orders.update` | `Order\OrderController@update` | `orders.view, orders.edit` | Login+verifiziert |
| DELETE | `/orders/{order}` | `orders.destroy` | `Order\OrderController@destroy` | `orders.view, orders.delete` | Login+verifiziert |
| GET | `/orders/{order}/edit` | `orders.edit` | `Order\OrderController@edit` | `orders.view, orders.edit` | Login+verifiziert |
| GET | `/orders/{order}/history` | `orders.history` | `Order\OrderController@history` | `orders.view, orders.view-history` | Login+verifiziert |
| PATCH | `/orders/{order}/status` | `orders.update-status` | `Order\OrderController@updateStatus` | `orders.view, orders.update-status` | Login+verifiziert |

### /packing

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| GET | `/packing` | `packing.index` | `Order\OrderPackingController@index` | `orders.pack` | Login+verifiziert |
| POST | `/packing/items/{orderItem}/pack` | `packing.pack-item` | `Order\OrderPackingController@packItem` | `orders.pack` | Login+verifiziert |
| DELETE | `/packing/items/{orderItem}/unpack` | `packing.unpack-item` | `Order\OrderPackingController@unpackItem` | `orders.pack` | Login+verifiziert |
| GET | `/packing/{order}` | `packing.show` | `Order\OrderPackingController@show` | `orders.pack` | Login+verifiziert |
| POST | `/packing/{order}/complete` | `packing.complete` | `Order\OrderPackingController@completeOrder` | `orders.pack` | Login+verifiziert |

### /password

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| PUT | `/password` | `password.update` | `Auth\PasswordController@update` | - | Login |

### /profile

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| GET | `/profile` | `profile.edit` | `ProfileController@edit` | - | Login |
| PATCH | `/profile` | `profile.update` | `ProfileController@update` | - | Login |
| DELETE | `/profile` | `profile.destroy` | `ProfileController@destroy` | - | Login |

### /protocols

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| GET | `/protocols` | `protocols.index` | `Rental\ProtocolController@index` | `protocols.view` | Login+verifiziert |
| GET | `/protocols/{protocol}` | `protocol.form` | `Rental\ProtocolController@showForm` | `protocols.view, protocols.create` | Login+verifiziert |
| GET | `/protocols/{protocol}/pdf` | `protocol.pdf` | `Rental\ProtocolController@pdf` | `protocols.view, protocols.pdf` | Login+verifiziert |
| POST | `/protocols/{protocol}/save` | `protocol.save` | `Rental\ProtocolController@saveForm` | `protocols.view, protocols.edit` | Login+verifiziert |
| POST | `/protocols/{protocol}/sign` | `protocol.sign` | `Rental\ProtocolController@sign` | `protocols.view, protocols.sign` | Login+verifiziert |

### /rentals

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| GET | `/rentals` | `rentals.index` | `Rental\RentalController@index` | `rentals.view` | Login+verifiziert |
| POST | `/rentals` | `rentals.store` | `Rental\RentalController@store` | `rentals.view, rentals.create` | Login+verifiziert |
| GET | `/rentals/create` | `rentals.create` | `Rental\RentalController@create` | `rentals.view, rentals.create` | Login+verifiziert |
| PUT | `/rentals/{rental}` | `rentals.update` | `Rental\RentalController@update` | `rentals.view, rentals.edit` | Login+verifiziert |
| DELETE | `/rentals/{rental}` | `rentals.destroy` | `Rental\RentalController@destroy` | `rentals.view, rentals.delete` | Login+verifiziert |
| GET | `/rentals/{rental}/edit` | `rentals.edit` | `Rental\RentalController@edit` | `rentals.view, rentals.edit` | Login+verifiziert |
| POST | `/rentals/{rental}/handover` | `rentals.handover` | `Rental\RentalController@createOrOpenHandover` | `rentals.view, rentals.handover` | Login+verifiziert |
| POST | `/rentals/{rental}/return` | `rentals.return` | `Rental\RentalController@createOrOpenReturn` | `rentals.view, rentals.return` | Login+verifiziert |

### /reset-password

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| POST | `/reset-password` | `password.store` | `Auth\NewPasswordController@store` | - | Login |
| GET | `/reset-password/{token}` | `password.reset` | `Auth\NewPasswordController@create` | - | Login |

### /verify-email

| Methode | URI | Name | Aktion | Recht | Zugriff |
|---|---|---|---|---|---|
| GET | `/verify-email` | `verification.notice` | `Auth\EmailVerificationPromptController` | - | Login |
| GET | `/verify-email/{id}/{hash}` | `verification.verify` | `Auth\VerifyEmailController` | - (Throttle 6,1) | Login |

