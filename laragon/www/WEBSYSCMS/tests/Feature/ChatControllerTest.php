<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_can_share_a_one_time_pin_in_the_assigned_request_chat(): void
    {
        [$customer, $technician, $requestId] = $this->createAssignedRequest();

        $this->actingAs($customer)
            ->postJson('/service-requests/'.$requestId.'/chat/messages', [
                'message_type' => 'location',
                'latitude' => 14.599512,
                'longitude' => 120.984222,
                'accuracy' => 12,
            ])
            ->assertOk()
            ->assertJsonPath('message.message_type', 'location')
            ->assertJsonPath('message.latitude', 14.599512);

        $this->actingAs($technician)
            ->getJson('/service-requests/'.$requestId.'/chat/messages?last_id=0')
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.sender_id', $customer->id)
            ->assertJsonPath('messages.0.longitude', 120.984222);
    }

    public function test_chat_is_limited_to_the_customer_and_assigned_technician(): void
    {
        [, , $requestId] = $this->createAssignedRequest();
        $otherCustomer = $this->createUser('customer', 'other-customer@example.com');

        $this->actingAs($otherCustomer)
            ->getJson('/service-requests/'.$requestId.'/chat/messages')
            ->assertNotFound();
    }

    public function test_assigned_customer_can_open_the_request_chat(): void
    {
        [$customer, , $requestId] = $this->createAssignedRequest();

        $this->actingAs($customer)
            ->get('/service-requests/'.$requestId.'/chat')
            ->assertOk()
            ->assertSee('Jump to latest messages')
            ->assertSee('data-request-chat-status', false)
            ->assertSee('data-share-chat-location', false);
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
            'ticket_no' => 'TST-CHAT-001',
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'category_id' => $categoryId,
            'service_type' => 'repair',
            'title' => 'Repair appliance',
            'description' => 'The appliance needs repair and inspection.',
            'address' => '123 Example Street',
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
