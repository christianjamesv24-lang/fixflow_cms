<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerAddressControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_customer_can_save_an_address_with_optional_coordinates(): void
    {
        $customer = $this->createUser('customer', 'customer@example.com');

        $this->actingAs($customer)
            ->post('/profile/addresses', [
                'label' => 'Home',
                'address' => '123 Example Street, Manila',
                'latitude' => '14.599512',
                'longitude' => '120.984222',
                'accuracy' => '8.5',
            ])
            ->assertRedirect('/profile#saved-addresses');

        $this->assertDatabaseHas('customer_addresses', [
            'customer_id' => $customer->id,
            'label' => 'Home',
            'address' => '123 Example Street, Manila',
            'latitude' => 14.599512,
            'longitude' => 120.984222,
        ]);
    }

    public function test_customer_cannot_delete_another_customers_saved_address(): void
    {
        $owner = $this->createUser('customer', 'owner@example.com');
        $otherCustomer = $this->createUser('customer', 'other@example.com');
        $addressId = DB::table('customer_addresses')->insertGetId([
            'customer_id' => $owner->id,
            'label' => 'Home',
            'address' => '123 Example Street',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($otherCustomer)
            ->post('/profile/addresses/'.$addressId.'/delete')
            ->assertNotFound();

        $this->assertDatabaseHas('customer_addresses', ['id' => $addressId, 'customer_id' => $owner->id]);
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
