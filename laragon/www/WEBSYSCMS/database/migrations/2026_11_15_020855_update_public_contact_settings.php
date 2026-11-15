<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ([
            'contact_email' => 'fixflow.cms@gmail.com',
            'contact_phone' => '0926 701 4324',
            'contact_address' => 'Pagkakaisa, Naujan, Oriental Mindoro',
            'business_hours' => 'Daily: 8:00 AM - 8:00 PM',
            'support_hours' => 'Daily: 8:00 AM - 8:00 PM',
        ] as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['setting_key' => $key],
                ['setting_value' => $value]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
