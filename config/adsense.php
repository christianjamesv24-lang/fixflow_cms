<?php

return [
    'enabled' => (bool) env('ADSENSE_ENABLED', false),
    'publisher_id' => env('ADSENSE_PUBLISHER_ID', ''),
    'public_slot_id' => env('ADSENSE_PUBLIC_SLOT_ID', ''),
];
