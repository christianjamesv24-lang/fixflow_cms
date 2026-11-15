<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TechnicianAvailableJobsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_technicians_can_browse_only_open_unassigned_customer_requests(): void
    {
        $technician = $this->createTechnician();
        $customer = $this->createUser('customer', 'customer@example.com');
        $otherTechnician = $this->createUser('technician', 'other-technician@example.com');
        $categoryId = (int) DB::table('service_categories')->where('slug', 'refrigerator')->value('id');

        $this->createRequest($customer, $categoryId, [
            'ticket_no' => 'SR-AVAILABLE-1001',
            'title' => 'Refrigerator not cooling',
            'description' => 'The refrigerator stopped cooling this morning.',
            'address' => '123 Private Street',
        ]);
        $this->createRequest($customer, $categoryId, [
            'ticket_no' => 'SR-ASSIGNED-1002',
            'title' => 'Already assigned request',
            'technician_id' => $otherTechnician->id,
            'status' => 'assigned',
        ]);
        $this->createRequest($customer, $categoryId, [
            'ticket_no' => 'SR-CANCELLED-1003',
            'title' => 'Cancelled request',
            'status' => 'cancelled',
        ]);

        $this->actingAs($technician)
            ->get('/technician/jobs/available')
            ->assertOk()
            ->assertSee('Available jobs')
            ->assertSee('SR-AVAILABLE-1001')
            ->assertSee('The refrigerator stopped cooling this morning.')
            ->assertDontSee('SR-ASSIGNED-1002')
            ->assertDontSee('SR-CANCELLED-1003')
            ->assertDontSee('123 Private Street')
            ->assertDontSee('customer@example.com');
    }

    public function test_available_jobs_can_be_searched_by_request_details(): void
    {
        $technician = $this->createTechnician();
        $customer = $this->createUser('customer', 'search-customer@example.com');
        $categoryId = (int) DB::table('service_categories')->where('slug', 'refrigerator')->value('id');

        $this->createRequest($customer, $categoryId, [
            'ticket_no' => 'SR-SEARCH-1001',
            'title' => 'Refrigerator not cooling',
            'description' => 'The refrigerator stopped cooling.',
        ]);

        $this->actingAs($technician)
            ->get('/technician/jobs/available?q=not%20cooling')
            ->assertOk()
            ->assertSee('SR-SEARCH-1001');

        $this->actingAs($technician)
            ->get('/technician/jobs/available?q=microwave')
            ->assertOk()
            ->assertSee('No available requests')
            ->assertDontSee('SR-SEARCH-1001');
    }

    private function createTechnician(): User
    {
        $technician = $this->createUser('technician', 'available-technician@example.com');

        DB::table('subscriptions')->insert([
            'user_id' => $technician->id,
            'plan' => 'monthly',
            'amount' => 500,
            'status' => 'active',
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $technician;
    }

    private function createUser(string $role, string $email): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => ucfirst($role).' User',
            'email' => $email,
            'password_hash' => bcrypt('password'),
            'role' => $role,
            'status' => 'active',
            'technician_approved_at' => $role === 'technician' ? now() : null,
            'avatar_color' => '#2563eb',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::query()->findOrFail($id);
    }

    private function createRequest(User $customer, int $categoryId, array $overrides = []): void
    {
        DB::table('service_requests')->insert(array_merge([
            'ticket_no' => 'SR-AVAILABLE-1000',
            'customer_id' => $customer->id,
            'category_id' => $categoryId,
            'service_type' => 'repair',
            'title' => 'Appliance needs service',
            'description' => 'Customer reported an appliance issue.',
            'address' => 'Private address',
            'priority' => 'medium',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
