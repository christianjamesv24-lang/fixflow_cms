<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChatNotificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_new_chat_messages_are_available_to_the_recipient_notification_poll(): void
    {
        $admin = $this->createUser('admin');
        $technician = $this->createUser('technician');

        $this->actingAs($admin)
            ->postJson('/chat/messages', ['message' => 'The repair is scheduled.'])
            ->assertOk()
            ->assertJsonPath('message.message', 'The repair is scheduled.');

        $this->actingAs($technician)
            ->getJson('/notifications/poll?after_id=0')
            ->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notification_count', 1)
            ->assertJsonPath('notifications.0.title', 'New message')
            ->assertJsonPath('notifications.0.body', 'Admin: The repair is scheduled.');

        $this->actingAs($technician)
            ->get('/chat')
            ->assertOk()
            ->assertSee('data-last-message-id="1"', false);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $technician->id,
            'title' => 'New message',
            'is_read' => true,
        ]);
    }

    public function test_notification_poll_only_returns_new_unread_notifications_for_the_signed_in_user(): void
    {
        $admin = $this->createUser('admin');
        $technician = $this->createUser('technician');

        DB::table('user_notifications')->insert([
            'user_id' => $technician->id,
            'title' => 'New message',
            'body' => 'A new chat message.',
            'type' => 'info',
            'is_read' => false,
            'created_at' => now(),
        ]);

        $this->actingAs($technician)
            ->getJson('/notifications/poll?after_id=1')
            ->assertOk()
            ->assertJsonCount(0, 'notifications')
            ->assertJsonPath('notification_count', 1);

        $this->actingAs($admin)
            ->getJson('/notifications/poll?after_id=0')
            ->assertOk()
            ->assertJsonCount(0, 'notifications')
            ->assertJsonPath('notification_count', 0);
    }

    public function test_technicians_receive_a_single_notification_when_their_subscription_is_near_expiry(): void
    {
        $technician = $this->createUser('technician');
        DB::table('subscriptions')
            ->where('user_id', $technician->id)
            ->update(['current_period_end' => now()->addDays(2)]);

        $this->actingAs($technician)
            ->getJson('/notifications/poll?after_id=0')
            ->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.title', 'Subscription ending soon')
            ->assertJsonPath('notifications.0.target_url', url('/technician/subscription'));

        $notificationId = (int) DB::table('user_notifications')->where('user_id', $technician->id)->value('id');
        $this->actingAs($technician)
            ->getJson('/notifications/poll?after_id='.$notificationId)
            ->assertOk()
            ->assertJsonCount(0, 'notifications');

        $this->assertDatabaseCount('user_notifications', 1);
        $this->assertNotNull(
            DB::table('subscriptions')->where('user_id', $technician->id)->value('expiring_notification_sent_at')
        );
    }

    public function test_guests_cannot_poll_user_notifications(): void
    {
        $this->getJson('/notifications/poll')
            ->assertRedirect('/login');
    }

    public function test_admin_portal_renders_the_live_notification_badge(): void
    {
        $admin = $this->createUser('admin');

        DB::table('user_notifications')->insert([
            'user_id' => $admin->id,
            'title' => 'New message',
            'body' => 'A new chat message.',
            'type' => 'info',
            'is_read' => false,
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('data-notification-poll', false)
            ->assertSee('data-notification-url=', false)
            ->assertSee('data-notification-target="'.url('/notifications').'"', false)
            ->assertSee('href="'.url('/notifications').'"', false)
            ->assertSee('1 unread notifications', false)
            ->assertSee('class="nav-count" data-notification-count>1</span>', false);
    }

    public function test_technician_portal_renders_the_live_notification_bell(): void
    {
        $technician = $this->createUser('technician');

        DB::table('user_notifications')->insert([
            'user_id' => $technician->id,
            'title' => 'New customer request available',
            'body' => 'A new service request is ready to review.',
            'type' => 'info',
            'is_read' => false,
            'target_url' => url('/technician/jobs/available'),
            'created_at' => now(),
        ]);

        $this->actingAs($technician)
            ->get('/technician/jobs/available')
            ->assertOk()
            ->assertSee('data-notification-poll', false)
            ->assertSee('data-notification-url=', false)
            ->assertSee('data-notification-target="'.url('/notifications').'"', false)
            ->assertSee('href="'.url('/notifications').'"', false)
            ->assertSee('1 unread notifications', false);
    }

    public function test_customer_can_open_the_notification_center_and_poll_updates(): void
    {
        $customer = $this->createUser('customer');

        DB::table('user_notifications')->insert([
            'user_id' => $customer->id,
            'title' => 'Request submitted',
            'body' => 'We received your service request.',
            'type' => 'info',
            'is_read' => false,
            'target_url' => url('/customer/requests/1'),
            'created_at' => now(),
        ]);

        $this->actingAs($customer)
            ->get('/notifications')
            ->assertOk()
            ->assertSee('Notifications')
            ->assertSee('Request submitted')
            ->assertSee('data-notification-target="'.url('/notifications').'"', false)
            ->assertSee('href="'.url('/notifications').'"', false);

        $this->actingAs($customer)
            ->getJson('/notifications/poll?after_id=0')
            ->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.title', 'Request submitted');
    }

    public function test_notification_center_lists_only_the_current_users_notifications_and_opens_owned_items(): void
    {
        $admin = $this->createUser('admin');
        $technician = $this->createUser('technician');

        $adminNotificationId = DB::table('user_notifications')->insertGetId([
            'user_id' => $admin->id,
            'title' => 'Request assigned',
            'body' => 'A request was assigned to a technician.',
            'type' => 'info',
            'is_read' => false,
            'target_url' => url('/admin/requests'),
            'created_at' => now(),
        ]);
        DB::table('user_notifications')->insert([
            'user_id' => $technician->id,
            'title' => 'Private technician notice',
            'body' => 'This notice belongs to another account.',
            'type' => 'info',
            'is_read' => false,
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/notifications')
            ->assertOk()
            ->assertSee('Notifications')
            ->assertSee('Request assigned')
            ->assertDontSee('Private technician notice');

        $this->actingAs($admin)
            ->get('/notifications/'.$adminNotificationId.'/open')
            ->assertRedirect('/admin/requests');

        $this->assertDatabaseHas('user_notifications', [
            'id' => $adminNotificationId,
            'user_id' => $admin->id,
            'is_read' => true,
        ]);

        $this->actingAs($technician)
            ->get('/notifications/'.$adminNotificationId.'/open')
            ->assertNotFound();
    }

    public function test_notification_center_can_mark_all_of_the_current_users_notifications_read(): void
    {
        $admin = $this->createUser('admin');
        $technician = $this->createUser('technician');

        foreach ([$admin->id, $technician->id] as $userId) {
            DB::table('user_notifications')->insert([
                'user_id' => $userId,
                'title' => 'Subscription update',
                'body' => 'A subscription update is available.',
                'type' => 'info',
                'is_read' => false,
                'created_at' => now(),
            ]);
        }

        $this->actingAs($admin)
            ->post('/notifications/read-all')
            ->assertRedirect('/notifications');

        $this->assertDatabaseHas('user_notifications', ['user_id' => $admin->id, 'is_read' => true]);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $technician->id, 'is_read' => false]);
    }

    public function test_opening_chat_does_not_clear_non_chat_notifications(): void
    {
        $admin = $this->createUser('admin');
        $notificationId = DB::table('user_notifications')->insertGetId([
            'user_id' => $admin->id,
            'title' => 'Subscription payment approved',
            'body' => 'Your subscription is active.',
            'type' => 'info',
            'is_read' => false,
            'created_at' => now(),
        ]);

        $this->actingAs($admin)->get('/chat')->assertOk();

        $this->assertDatabaseHas('user_notifications', [
            'id' => $notificationId,
            'is_read' => false,
        ]);
    }

    private function createUser(string $role): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => ucfirst($role),
            'email' => $role.'@example.com',
            'password_hash' => bcrypt('password'),
            'role' => $role,
            'status' => 'active',
            'technician_approved_at' => $role === 'technician' ? now() : null,
            'avatar_color' => '#2563eb',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($role === 'technician') {
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
