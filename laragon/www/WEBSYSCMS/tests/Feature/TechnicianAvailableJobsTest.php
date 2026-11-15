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
            'address' => '123 Private Street, Cebu City, Central Visayas',
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
            ->assertSee('Cebu City, Central Visayas')
            ->assertDontSee('SR-ASSIGNED-1002')
            ->assertDontSee('SR-CANCELLED-1003')
            ->assertDontSee('123 Private Street')
            ->assertDontSee('customer@example.com');
    }

    public function test_technicians_can_claim_open_requests_and_view_customer_details_afterward(): void
    {
        $technician = $this->createTechnician();
        $customer = $this->createUser('customer', 'claim-customer@example.com');
        $categoryId = (int) DB::table('service_categories')->where('slug', 'refrigerator')->value('id');
        $this->createRequest($customer, $categoryId, [
            'ticket_no' => 'SR-CLAIM-1001',
            'address' => '123 Private Street, Cebu City, Central Visayas',
        ]);
        $requestId = (int) DB::table('service_requests')->where('ticket_no', 'SR-CLAIM-1001')->value('id');

        $this->actingAs($technician)
            ->post('/technician/jobs/'.$requestId.'/claim')
            ->assertRedirect('/technician/jobs/'.$requestId);

        $this->assertDatabaseHas('service_requests', [
            'id' => $requestId,
            'technician_id' => $technician->id,
            'status' => 'accepted',
        ]);
        $this->assertNotNull(DB::table('service_requests')->where('id', $requestId)->value('assigned_at'));
        $this->assertDatabaseHas('request_status_history', [
            'request_id' => $requestId,
            'status' => 'accepted',
            'note' => 'Technician claimed the open request.',
            'changed_by' => $technician->id,
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $customer->id,
            'title' => 'Your request was claimed',
            'target_url' => url('/customer/requests/'.$requestId),
        ]);

        $this->actingAs($technician)
            ->get('/technician/jobs/'.$requestId)
            ->assertOk()
            ->assertSee('claim-customer@example.com')
            ->assertSee('123 Private Street');
    }

    public function test_a_second_technician_cannot_claim_a_request_already_claimed_by_another(): void
    {
        $firstTechnician = $this->createTechnician('first-claim-technician@example.com');
        $secondTechnician = $this->createTechnician('second-claim-technician@example.com');
        $customer = $this->createUser('customer', 'first-claim-customer@example.com');
        $categoryId = (int) DB::table('service_categories')->where('slug', 'refrigerator')->value('id');
        $this->createRequest($customer, $categoryId, [
            'ticket_no' => 'SR-FIRST-CLAIM-1001',
        ]);
        $requestId = (int) DB::table('service_requests')->where('ticket_no', 'SR-FIRST-CLAIM-1001')->value('id');

        $this->actingAs($firstTechnician)
            ->post('/technician/jobs/'.$requestId.'/claim')
            ->assertRedirect('/technician/jobs/'.$requestId);

        $this->actingAs($secondTechnician)
            ->post('/technician/jobs/'.$requestId.'/claim')
            ->assertRedirect('/technician/jobs/available');

        $this->assertDatabaseHas('service_requests', [
            'id' => $requestId,
            'technician_id' => $firstTechnician->id,
            'status' => 'accepted',
        ]);
        $this->assertSame(
            1,
            DB::table('request_status_history')
                ->where('request_id', $requestId)
                ->where('status', 'accepted')
                ->count()
        );
    }

    public function test_customers_cannot_claim_an_open_request(): void
    {
        $customer = $this->createUser('customer', 'cannot-claim@example.com');
        $categoryId = (int) DB::table('service_categories')->where('slug', 'refrigerator')->value('id');
        $this->createRequest($customer, $categoryId, [
            'ticket_no' => 'SR-CUSTOMER-CLAIM-1001',
        ]);
        $requestId = (int) DB::table('service_requests')->where('ticket_no', 'SR-CUSTOMER-CLAIM-1001')->value('id');

        $this->actingAs($customer)
            ->post('/technician/jobs/'.$requestId.'/claim')
            ->assertForbidden();

        $this->assertDatabaseHas('service_requests', [
            'id' => $requestId,
            'technician_id' => null,
            'status' => 'pending',
        ]);
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

    public function test_accepting_a_job_notifies_the_customer(): void
    {
        $technician = $this->createTechnician();
        $customer = $this->createUser('customer', 'accept-customer@example.com');
        $categoryId = (int) DB::table('service_categories')->where('slug', 'refrigerator')->value('id');

        $this->createRequest($customer, $categoryId, [
            'ticket_no' => 'SR-ACCEPT-1001',
            'technician_id' => $technician->id,
            'status' => 'assigned',
        ]);
        $requestId = (int) DB::table('service_requests')->where('ticket_no', 'SR-ACCEPT-1001')->value('id');

        $this->actingAs($technician)
            ->post('/technician/jobs/'.$requestId.'/accept')
            ->assertRedirect('/technician/jobs/'.$requestId);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $customer->id,
            'title' => 'Your request was accepted',
            'target_url' => url('/customer/requests/'.$requestId),
        ]);
    }

    private function createTechnician(string $email = 'available-technician@example.com'): User
    {
        $technician = $this->createUser('technician', $email);

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
