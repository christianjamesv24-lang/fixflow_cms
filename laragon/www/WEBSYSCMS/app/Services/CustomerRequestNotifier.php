<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CustomerRequestNotifier
{
    public function notify(int $customerId, int $requestId, string $title, string $body): void
    {
        DB::table('user_notifications')->insert([
            'user_id' => $customerId,
            'title' => $title,
            'body' => $body,
            'type' => 'info',
            'is_read' => false,
            'target_url' => url('/customer/requests/'.$requestId),
            'created_at' => now(),
        ]);
    }
}
