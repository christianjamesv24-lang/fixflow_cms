<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerRequestFormTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_new_request_form_lists_default_active_appliance_categories(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($customer)
            ->get('/customer/requests/new')
            ->assertOk()
            ->assertSee('Air Conditioner')
            ->assertSee('Refrigerator')
            ->assertSee('Washing Machine')
            ->assertSee('Water Heater');
    }

    public function test_missing_uploaded_logo_uses_the_default_blue_brand_mark(): void
    {
        DB::table('settings')->updateOrInsert(
            ['setting_key' => 'site_logo'],
            ['setting_value' => 'uploads/branding/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.png']
        );
        settings(true);

        $this->get('/login')
            ->assertOk()
            ->assertSee('<span class="brand-mark">', false)
            ->assertDontSee('<img class="brand-mark-image"', false);
    }

    private function createCustomer(): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password_hash' => bcrypt('password'),
            'role' => 'customer',
            'status' => 'active',
            'avatar_color' => '#2563eb',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::query()->findOrFail($id);
    }
}
