<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TechnicianSubscriptionTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'subscriptions.prices.monthly' => 500,
            'subscriptions.prices.yearly' => 5000,
            'subscriptions.gcash.account_name' => 'FixitNow',
            'subscriptions.gcash.account_number' => '09171234567',
            'subscriptions.proof_disk' => 'subscription_proofs',
        ]);
        Storage::fake('subscription_proofs');
    }

    public function test_unsubscribed_technicians_are_redirected_to_subscription_page(): void
    {
        $technician = $this->createUser('technician');

        $this->actingAs($technician)
            ->get('/technician/schedule')
            ->assertRedirect('/technician/subscription');

        $this->actingAs($technician)
            ->get('/technician/subscription')
            ->assertOk()
            ->assertSee('₱500.00')
            ->assertSee('₱5,000.00')
            ->assertSee('09171234567');
    }

    public function test_admin_approval_activates_and_extends_the_selected_subscription(): void
    {
        $admin = $this->createUser('admin');
        $technician = $this->createUser('technician');
        $paymentId = $this->submitPayment($technician, 'GCASH-REF-1001', 'yearly');

        $this->actingAs($admin)
            ->post('/admin/subscriptions/'.$paymentId.'/approve')
            ->assertRedirect('/admin/subscriptions');

        $this->assertDatabaseHas('subscription_payments', [
            'id' => $paymentId,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);

        $subscription = DB::table('subscriptions')->where('user_id', $technician->id)->first();
        $this->assertSame('active', $subscription->status);
        $this->assertSame('yearly', $subscription->plan);
        $this->assertSame(5000.0, (float) $subscription->amount);
        $this->assertSame(
            now()->addYear()->toDateString(),
            \Illuminate\Support\Carbon::parse($subscription->current_period_end)->toDateString()
        );

        $this->actingAs($technician)
            ->get('/technician/schedule')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/subscriptions')
            ->assertOk()
            ->assertSee('Approved subscription income')
            ->assertSee('₱5,000.00')
            ->assertSee('View receipt');

        $this->actingAs($admin)
            ->get('/admin/subscriptions/'.$paymentId.'/proof')
            ->assertOk();

        $this->actingAs($technician)
            ->get('/admin/subscriptions/'.$paymentId.'/proof')
            ->assertForbidden();
    }

    public function test_renewal_extends_an_existing_active_period_instead_of_replacing_it(): void
    {
        $admin = $this->createUser('admin');
        $technician = $this->createUser('technician');
        $currentEnd = now()->addDays(10);

        DB::table('subscriptions')->insert([
            'user_id' => $technician->id,
            'plan' => 'monthly',
            'amount' => 500,
            'status' => 'active',
            'current_period_start' => now()->subMonth(),
            'current_period_end' => $currentEnd,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($technician)
            ->get('/technician/subscription')
            ->assertOk()
            ->assertSee('Renew or extend your plan');

        $paymentId = $this->submitPayment($technician, 'GCASH-REF-3003', 'yearly');

        $this->actingAs($admin)
            ->post('/admin/subscriptions/'.$paymentId.'/approve')
            ->assertRedirect('/admin/subscriptions');

        $renewal = DB::table('subscriptions')
            ->where('user_id', $technician->id)
            ->where('plan', 'yearly')
            ->first();

        $this->assertSame(
            $currentEnd->toDateString(),
            \Illuminate\Support\Carbon::parse($renewal->current_period_start)->toDateString()
        );
        $this->assertSame(
            $currentEnd->copy()->addYear()->toDateString(),
            \Illuminate\Support\Carbon::parse($renewal->current_period_end)->toDateString()
        );
    }

    public function test_payment_reference_cannot_be_submitted_twice_and_admin_can_reject_pending_payment(): void
    {
        $admin = $this->createUser('admin');
        $firstTechnician = $this->createUser('technician');
        $secondTechnician = $this->createUser('technician', 'another-technician@example.com');
        $paymentId = $this->submitPayment($firstTechnician, 'GCASH-REF-2002');

        $this->actingAs($secondTechnician)
            ->from('/technician/subscription')
            ->post('/technician/subscription/payment', [
                'plan' => 'monthly',
                'gcash_reference' => 'GCASH-REF-2002',
                'proof' => UploadedFile::fake()->image('receipt.png'),
            ])
            ->assertRedirect('/technician/subscription')
            ->assertSessionHas('_form_errors.gcash_reference');

        $this->actingAs($admin)
            ->post('/admin/subscriptions/'.$paymentId.'/reject', ['review_note' => 'Reference not found'])
            ->assertRedirect('/admin/subscriptions');

        $this->assertDatabaseHas('subscription_payments', [
            'id' => $paymentId,
            'status' => 'rejected',
            'review_note' => 'Reference not found',
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $firstTechnician->id,
            'status' => 'rejected',
        ]);
    }

    public function test_seed_does_not_delete_users_or_create_default_accounts(): void
    {
        $existing = $this->createUser('customer');

        $this->seed();

        $this->assertDatabaseHas('users', ['id' => $existing->id]);
        $this->assertDatabaseMissing('users', ['email' => 'admin@fixflowservice.com']);
        $this->assertDatabaseMissing('users', ['email' => 'technician@fixflowservice.com']);
    }

    public function test_payment_is_not_accepted_until_gcash_account_details_are_configured(): void
    {
        config(['subscriptions.gcash.account_number' => '']);
        $technician = $this->createUser('technician');

        $this->actingAs($technician)
            ->from('/technician/subscription')
            ->post('/technician/subscription/payment', [
                'plan' => 'monthly',
                'gcash_reference' => 'GCASH-REF-4004',
                'proof' => UploadedFile::fake()->image('receipt.png'),
            ])
            ->assertRedirect('/technician/subscription')
            ->assertSessionHas('_form_errors.gcash_reference');

        $this->assertDatabaseCount('subscription_payments', 0);
    }

    public function test_non_image_receipts_are_rejected(): void
    {
        $technician = $this->createUser('technician');

        $this->actingAs($technician)
            ->from('/technician/subscription')
            ->post('/technician/subscription/payment', [
                'plan' => 'monthly',
                'gcash_reference' => 'GCASH-REF-5005',
                'proof' => UploadedFile::fake()->create('receipt.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect('/technician/subscription')
            ->assertSessionHas('_form_errors.proof');

        $this->assertDatabaseCount('subscription_payments', 0);
    }

    private function submitPayment(User $technician, string $reference, string $plan = 'monthly'): int
    {
        $this->actingAs($technician)
            ->post('/technician/subscription/payment', [
                'plan' => $plan,
                'gcash_reference' => $reference,
                'proof' => UploadedFile::fake()->image('receipt.png'),
            ])
            ->assertRedirect('/technician/subscription');

        $paymentId = (int) DB::table('subscription_payments')
            ->where('gcash_reference', $reference)
            ->value('id');

        $this->assertGreaterThan(0, $paymentId);
        $proofPath = DB::table('subscription_payments')->where('id', $paymentId)->value('proof_path');
        $this->assertNotEmpty($proofPath);
        Storage::disk('subscription_proofs')->assertExists($proofPath);

        return $paymentId;
    }

    private function createUser(string $role, ?string $email = null): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => ucfirst($role),
            'email' => $email ?? $role.'@example.com',
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
