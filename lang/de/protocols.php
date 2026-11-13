<?php

return [
    'title' => 'Protokolle',
    'protocol' => 'Protokoll',
    'protocols' => 'Protokolle',
    'create' => 'Neues Protokoll erstellen',
    'edit' => 'Protokoll bearbeiten',
    'view' => 'Protokoll anzeigen',
    'finalize' => 'Protokoll finalisieren',

    'types' => [
        'handover' => 'Übergabe',
        'return' => 'Rücknahme',
    ],

    'states' => [
        'ok' => 'OK',
        'missing' => 'Fehlt',
        'damaged' => 'Beschädigt',
    ],

    'roles' => [
        'tenant' => 'Mieter:in',
        'landlord' => 'Vermieter:in',
    ],

    'list' => [
        'type' => 'Typ',
        'tenant' => 'Mieter:in',
        'hall' => 'Halle',
        'created' => 'Erstellt',
        'pdf' => 'PDF',
        'action' => 'Aktion',
        'pdf_available' => 'vorhanden',
        'open' => 'öffnen',
    ],

    'form' => [
        'tenant' => 'Mieter',
        'period' => 'Zeitraum',
        'finalized_notice' => 'Dieses Protokoll wurde finalisiert. Änderungen sind nicht mehr möglich.',
        'electricity_meter' => 'Stromzähler',
        'water_meter' => 'Wasserzähler',
        'position' => 'Position',
        'status' => 'Status',
        'comment' => 'Kommentar',
        'charge' => 'Gebühr (€)',
        'photos' => 'Fotos',
        'notes' => 'Notizen',
        'signature' => 'Unterschrift',
        'role' => 'Rolle',
        'name' => 'Name',
        'sign_and_save' => 'Unterschreiben & Speichern',
        'open_final_pdf' => 'Finales PDF öffnen',
        'signatures_done' => 'Unterschriften abgeschlossen – keine weitere Signatur möglich.',
    ],

    'messages' => [
        'saved' => 'Protokoll gespeichert.',
        'already_finalized' => 'Protokoll ist bereits finalisiert und kann nicht mehr geändert werden.',
        'already_closed' => 'Dieses Protokoll ist bereits abgeschlossen.',
        'save_error' => 'Ein Fehler ist aufgetreten beim Speichern.',
        'sign_error' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.',
        'too_many_photos' => 'Maximale Anzahl von :max Fotos pro Protokoll erreicht.',
        'invalid_signature' => 'Die Unterschrift ist ungültig. Bitte unterschreiben Sie erneut.',
        'signature_not_png' => 'Die Unterschrift muss ein PNG-Bild sein.',
        'signature_too_large' => 'Die Unterschrift ist zu groß.',
        'finalized' => 'Protokoll finalisiert und PDF erstellt.',
        'signature_saved' => 'Unterschrift gespeichert. Ausstehend: :role.',
        'role_tenant' => 'Mieter',
        'role_landlord' => 'Vermieter',
        'confirm_finalize' => 'Möchten Sie dieses Protokoll wirklich finalisieren? Nach der Finalisierung kann es nicht mehr bearbeitet werden.',
    ],
];
