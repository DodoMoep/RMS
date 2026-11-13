<?php

return [
    'title' => 'Vermietungen',
    'rental' => 'Vermietung',
    'rentals' => 'Vermietungen',
    'create' => 'Neue Vermietung erstellen',
    'edit' => 'Vermietung bearbeiten',
    'delete' => 'Vermietung löschen',
    'new' => 'Neu',
    'new_title' => 'Neue Vermietung',

    'tenant' => 'Mieter:in',
    'hall' => 'Halle',
    'start' => 'Start',
    'end' => 'Ende',
    'start_date' => 'Startdatum',
    'end_date' => 'Enddatum',
    'status' => 'Status',
    'price' => 'Preis',
    'deposit' => 'Kaution',
    'price_eur' => 'Preis (€)',
    'deposit_eur' => 'Kaution (€)',
    'total_price' => 'Gesamtpreis',

    'statuses' => [
        'scheduled' => 'geplant',
        'active' => 'aktiv',
        'closed' => 'geschlossen',
        'cancelled' => 'storniert',
    ],

    'actions' => [
        'open_handover' => 'Übergabe öffnen',
        'create_handover' => 'Übergabe anlegen',
        'open_return' => 'Rücknahme öffnen',
        'create_return' => 'Rücknahme anlegen',
    ],

    'messages' => [
        'created' => 'Vermietung angelegt.',
        'updated' => 'Vermietung aktualisiert.',
        'deleted' => 'Vermietung gelöscht.',
        'has_protocols' => 'Vermietung kann nicht gelöscht werden, da bereits Protokolle vorhanden sind.',
        'hall_booked' => 'Diese Halle ist im gewählten Zeitraum bereits vermietet.',
        'handover_error' => 'Fehler beim Erstellen des Übergabeprotokolls.',
        'return_error' => 'Fehler beim Erstellen des Rückgabeprotokolls.',
        'confirm_delete' => 'Möchten Sie diese Vermietung wirklich löschen?',
    ],
];
