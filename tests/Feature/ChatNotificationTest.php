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
            ->assertSee('1 unread notifications', false)
            ->assertSee('class="nav-count" data-notification-count>1</span>', false);
    }

    private function createUser(string $role): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => ucfirst($role),
            'email' => $role.'@example.com',
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
