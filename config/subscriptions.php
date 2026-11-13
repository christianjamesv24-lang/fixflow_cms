<?php

return [
    'proof_disk' => env('SUBSCRIPTION_PROOF_DISK', 'local'),

    'prices' => [
        'monthly' => (int) env('TECHNICIAN_SUBSCRIPTION_MONTHLY_PRICE', 500),
        'yearly' => (int) env('TECHNICIAN_SUBSCRIPTION_YEARLY_PRICE', 5000),
    ],

    'gcash' => [
        'account_name' => env('GCASH_ACCOUNT_NAME', ''),
        'account_number' => env('GCASH_ACCOUNT_NUMBER', ''),
    ],
];
