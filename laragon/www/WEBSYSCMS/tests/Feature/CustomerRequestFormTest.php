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

    public function test_new_customer_requests_notify_only_eligible_technicians(): void
    {
        $customer = $this->createCustomer();
        $technician = $this->createTechnician('available@example.com', true);
        $inactiveTechnician = $this->createTechnician('inactive@example.com', false);
        $categoryId = DB::table('service_categories')->where('slug', 'refrigerator')->value('id');

        $response = $this->actingAs($customer)
            ->post('/customer/requests', [
                'category_id' => $categoryId,
                'service_type' => 'repair',
                'title' => 'Refrigerator not cooling',
                'description' => 'The refrigerator stopped cooling this morning.',
                'brand' => '',
                'model' => '',
                'serial_no' => '',
                'address' => '123 Customer Private Street',
                'latitude' => '',
                'longitude' => '',
                'location_accuracy' => '',
                'priority' => 'medium',
                'preferred_date' => '',
                'preferred_time' => '',
            ]);

        $createdRequest = DB::table('service_requests')->where('title', 'Refrigerator not cooling')->first();
        $this->assertNotNull($createdRequest);
        $response->assertRedirect('/customer/requests/'.$createdRequest->id);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $technician->id,
            'title' => 'New customer request available',
            'target_url' => url('/technician/jobs/available'),
        ]);
        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $inactiveTechnician->id,
        ]);

        $this->actingAs($technician)
            ->getJson('/notifications/poll?after_id=0')
            ->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.title', 'New customer request available')
            ->assertJsonPath('notifications.0.target_url', url('/technician/jobs/available'));
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

    private function createTechnician(string $email, bool $hasActiveSubscription): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Test Technician',
            'email' => $email,
            'password_hash' => bcrypt('password'),
            'role' => 'technician',
            'status' => 'active',
            'technician_approved_at' => now(),
            'avatar_color' => '#2563eb',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($hasActiveSubscription) {
            DB::table('subscriptions')->insert([
                'user_id' => $id,
                'plan' => 'monthly',
                'amount' => 500,
                'status' => 'active',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return User::query()->findOrFail($id);
    }
}
