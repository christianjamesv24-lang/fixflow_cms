<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TechnicianLocationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_assigned_technician_can_share_location_with_the_request_customer(): void
    {
        [$customer, $technician, $requestId] = $this->createAssignedRequest();

        $this->actingAs($technician)
            ->postJson('/service-requests/'.$requestId.'/location', [
                'latitude' => 14.599512,
                'longitude' => 120.984222,
                'accuracy' => 9,
            ])
            ->assertOk();

        $this->actingAs($customer)
            ->getJson('/service-requests/'.$requestId.'/location')
            ->assertOk()
            ->assertJsonPath('customer_location.latitude', 14.6)
            ->assertJsonPath('technician_location.latitude', 14.599512);
    }

    public function test_only_the_assigned_technician_can_update_a_request_location(): void
    {
        [$customer, , $requestId] = $this->createAssignedRequest();
        $otherTechnician = $this->createUser('technician', 'other-tech@example.com');

        $this->actingAs($otherTechnician)
            ->postJson('/service-requests/'.$requestId.'/location', [
                'latitude' => 14.599512,
                'longitude' => 120.984222,
            ])
            ->assertNotFound();

        $this->actingAs($customer)
            ->postJson('/service-requests/'.$requestId.'/location', [
                'latitude' => 14.599512,
                'longitude' => 120.984222,
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('technician_live_locations', ['request_id' => $requestId]);
    }

    public function test_invalid_gps_coordinates_are_rejected(): void
    {
        [, $technician, $requestId] = $this->createAssignedRequest();

        $this->actingAs($technician)
            ->postJson('/service-requests/'.$requestId.'/location', [
                'latitude' => 91,
                'longitude' => 181,
            ])
            ->assertUnprocessable();
    }

    private function createAssignedRequest(): array
    {
        $customer = $this->createUser('customer', 'customer@example.com');
        $technician = $this->createUser('technician', 'technician@example.com');
        $categoryId = DB::table('service_categories')->insertGetId([
            'name' => 'Appliance repair',
            'slug' => 'appliance-repair',
            'icon' => 'wrench',
            'base_price' => 100,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $requestId = DB::table('service_requests')->insertGetId([
            'ticket_no' => 'TST-LOC-001',
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'category_id' => $categoryId,
            'service_type' => 'repair',
            'title' => 'Repair appliance',
            'description' => 'The appliance needs repair and inspection.',
            'address' => '123 Example Street',
            'latitude' => 14.6,
            'longitude' => 121.0,
            'priority' => 'medium',
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$customer, $technician, $requestId];
    }

    private function createUser(string $role, string $email): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => ucfirst($role),
            'email' => $email,
            'password_hash' => bcrypt('password'),
            'role' => $role,
            'status' => 'active',
            'avatar_color' => '#2563eb',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::query()->findOrFail($id);
    }
}
