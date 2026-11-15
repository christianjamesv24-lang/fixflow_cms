<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('service_categories')->insertOrIgnore([
            [
                'name' => 'Air Conditioner',
                'slug' => 'air-conditioner',
                'icon' => 'snowflake',
                'short_description' => 'Cleaning, repair, and installation for split and window units.',
                'base_price' => 850,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Refrigerator',
                'slug' => 'refrigerator',
                'icon' => 'fridge',
                'short_description' => 'Cooling problems, leaks, and inverter board repairs.',
                'base_price' => 950,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Washing Machine',
                'slug' => 'washing-machine',
                'icon' => 'washer',
                'short_description' => 'Top-load and front-load repair, cleaning, and setup.',
                'base_price' => 750,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Oven & Stove',
                'slug' => 'oven-stove',
                'icon' => 'oven',
                'short_description' => 'Gas and electric range repair, cleaning, and safety checks.',
                'base_price' => 800,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Microwave',
                'slug' => 'microwave',
                'icon' => 'microwave',
                'short_description' => 'Heating failures, turntable, and door switch repairs.',
                'base_price' => 600,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Dishwasher',
                'slug' => 'dishwasher',
                'icon' => 'droplet',
                'short_description' => 'Drainage, spray arm, and control fault diagnosis.',
                'base_price' => 700,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Water Heater',
                'slug' => 'water-heater',
                'icon' => 'activity',
                'short_description' => 'Tank, element, and safety valve servicing.',
                'base_price' => 650,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Dryer',
                'slug' => 'dryer',
                'icon' => 'refresh',
                'short_description' => 'Vent cleaning, heating, and drum repairs.',
                'base_price' => 700,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        // Keep category rows on rollback because customer requests may reference them.
    }
};
