<?php

return [
    'title' => 'Protocols',
    'protocol' => 'Protocol',
    'protocols' => 'Protocols',
    'create' => 'Create Protocol',
    'edit' => 'Edit Protocol',
    'view' => 'View Protocol',
    'finalize' => 'Finalize Protocol',

    'types' => [
        'handover' => 'Handover',
        'return' => 'Return',
    ],

    'states' => [
        'ok' => 'OK',
        'missing' => 'Missing',
        'damaged' => 'Damaged',
    ],

    'roles' => [
        'tenant' => 'Tenant',
        'landlord' => 'Landlord',
    ],

    'list' => [
        'type' => 'Type',
        'tenant' => 'Tenant',
        'hall' => 'Hall',
        'created' => 'Created',
        'pdf' => 'PDF',
        'action' => 'Action',
        'pdf_available' => 'available',
        'open' => 'open',
    ],

    'form' => [
        'tenant' => 'Tenant',
        'period' => 'Period',
        'finalized_notice' => 'This protocol has been finalized. Changes are no longer possible.',
        'electricity_meter' => 'Electricity meter',
        'water_meter' => 'Water meter',
        'position' => 'Item',
        'status' => 'Status',
        'comment' => 'Comment',
        'charge' => 'Charge (€)',
        'photos' => 'Photos',
        'notes' => 'Notes',
        'signature' => 'Signature',
        'role' => 'Role',
        'name' => 'Name',
        'sign_and_save' => 'Sign & Save',
        'open_final_pdf' => 'Open final PDF',
        'signatures_done' => 'Signatures complete – no further signature possible.',
    ],

    'messages' => [
        'saved' => 'Protocol saved.',
        'already_finalized' => 'The protocol is already finalized and can no longer be changed.',
        'already_closed' => 'This protocol is already closed.',
        'save_error' => 'An error occurred while saving.',
        'sign_error' => 'An error occurred. Please try again.',
        'too_many_photos' => 'The maximum of :max photos per protocol has been reached.',
        'invalid_signature' => 'The signature is invalid. Please sign again.',
        'signature_not_png' => 'The signature must be a PNG image.',
        'signature_too_large' => 'The signature is too large.',
        'finalized' => 'Protocol finalized and PDF created.',
        'signature_saved' => 'Signature saved. Pending: :role.',
        'role_tenant' => 'Tenant',
        'role_landlord' => 'Landlord',
        'confirm_finalize' => 'Are you sure you want to finalize this protocol? Once finalized, it cannot be edited.',
    ],
];
