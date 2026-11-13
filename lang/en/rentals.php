<?php

return [
    'title' => 'Rentals',
    'rental' => 'Rental',
    'rentals' => 'Rentals',
    'create' => 'Create Rental',
    'edit' => 'Edit Rental',
    'delete' => 'Delete Rental',
    'new' => 'New',
    'new_title' => 'New rental',

    'tenant' => 'Tenant',
    'hall' => 'Hall',
    'start' => 'Start',
    'end' => 'End',
    'start_date' => 'Start Date',
    'end_date' => 'End Date',
    'status' => 'Status',
    'price' => 'Price',
    'deposit' => 'Deposit',
    'price_eur' => 'Price (€)',
    'deposit_eur' => 'Deposit (€)',
    'total_price' => 'Total Price',

    'statuses' => [
        'scheduled' => 'scheduled',
        'active' => 'active',
        'closed' => 'closed',
        'cancelled' => 'cancelled',
    ],

    'actions' => [
        'open_handover' => 'Open handover',
        'create_handover' => 'Create handover',
        'open_return' => 'Open return',
        'create_return' => 'Create return',
    ],

    'messages' => [
        'created' => 'Rental created.',
        'updated' => 'Rental updated.',
        'deleted' => 'Rental deleted.',
        'has_protocols' => 'The rental cannot be deleted because protocols already exist.',
        'hall_booked' => 'This hall is already booked for the selected period.',
        'handover_error' => 'Error creating the handover protocol.',
        'return_error' => 'Error creating the return protocol.',
        'confirm_delete' => 'Are you sure you want to delete this rental?',
    ],
];
