<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TechnicianSubscriptionActivator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
            'services.paymongo.secret_key' => null,
            'services.paymongo.webhook_secret' => null,
            'services.payments.demo_mode' => false,
            'services.payments.allow_production_demo' => false,
            'services.payments.vercel_environment' => null,
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
            ->assertSee('09171234567')
            ->assertSee('Choose a plan');
    }

    public function test_technicians_can_register_pay_before_approval_and_get_approved_with_their_subscription(): void
    {
        $this->post('/register', [
            'role' => 'technician',
            'name' => 'New Technician',
            'email' => 'new-technician@example.com',
            'phone' => '09171234567',
            'address' => 'Quezon City',
            'password' => 'TechnicianPass2026',
            'password_confirmation' => 'TechnicianPass2026',
        ])->assertRedirect('/technician/subscription');

        $technician = User::query()->where('email', 'new-technician@example.com')->firstOrFail();

        $this->assertSame('technician', $technician->role);
        $this->assertNull($technician->technician_approved_at);
        $this->assertAuthenticatedAs($technician);

        $this->actingAs($technician)
            ->get('/technician/schedule')
            ->assertRedirect('/technician/subscription');

        $this->actingAs($technician)
            ->get('/technician/subscription')
            ->assertOk()
            ->assertSee('Account awaiting admin approval');

        $paymentId = $this->submitPayment($technician, 'GCASH-REF-TECH-1001');
        $admin = $this->createUser('admin');

        $this->actingAs($admin)
            ->post('/admin/subscriptions/'.$paymentId.'/approve')
            ->assertRedirect('/admin/subscriptions');

        $technician->refresh();
        $this->assertNotNull($technician->technician_approved_at);
        $this->assertDatabaseHas('subscription_payments', [
            'id' => $paymentId,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $technician->id,
            'status' => 'active',
        ]);
        $this->actingAs($technician)
            ->getJson('/notifications/poll?after_id=0')
            ->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.title', 'Technician account approved')
            ->assertJsonPath('notifications.0.target_url', url('/technician'));

        $this->actingAs($technician)
            ->get('/technician/schedule')
            ->assertOk();
    }

    public function test_public_registration_remains_customer_by_default(): void
    {
        $this->post('/register', [
            'name' => 'New Customer',
            'email' => 'new-customer@example.com',
            'password' => 'CustomerPass2026',
            'password_confirmation' => 'CustomerPass2026',
        ])->assertRedirect('/customer');

        $this->assertDatabaseHas('users', [
            'email' => 'new-customer@example.com',
            'role' => 'customer',
        ]);
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
            Carbon::parse($subscription->current_period_end)->toDateString()
        );

        $this->actingAs($technician)
            ->get('/technician/schedule')
            ->assertOk();

        $this->actingAs($technician)
            ->get('/technician/subscription')
            ->assertOk()
            ->assertSee('Payment information')
            ->assertSee('₱5,000.00')
            ->assertSee('GCash transfer')
            ->assertSee('GCASH-REF-1001')
            ->assertSee('Extend or renew your plan')
            ->assertSee('class="btn btn-ghost mt-2"', false)
            ->assertDontSee('Renew or extend your plan');

        $this->actingAs($technician)
            ->get('/technician/subscription?extend=1')
            ->assertOk()
            ->assertSee('Renew or extend your plan');

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
            ->assertSee('Extend or renew your plan')
            ->assertDontSee('Renew or extend your plan');

        $this->actingAs($technician)
            ->get('/technician/subscription?extend=1')
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
            Carbon::parse($renewal->current_period_start)->toDateString()
        );
        $this->assertSame(
            $currentEnd->copy()->addYear()->toDateString(),
            Carbon::parse($renewal->current_period_end)->toDateString()
        );

        $this->actingAs($technician)
            ->getJson('/notifications/poll?after_id=0')
            ->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.title', 'Subscription extended')
            ->assertJsonPath('notifications.0.target_url', url('/technician/subscription'));
    }

    public function test_technician_can_cancel_at_period_end_and_keep_access_until_then(): void
    {
        $admin = $this->createUser('admin');
        $technician = $this->createUser('technician');
        $paymentId = $this->submitPayment($technician, 'GCASH-REF-CANCEL-1001');

        $this->actingAs($admin)
            ->post('/admin/subscriptions/'.$paymentId.'/approve')
            ->assertRedirect('/admin/subscriptions');

        $subscription = DB::table('subscriptions')->where('user_id', $technician->id)->first();
        $periodEnd = $subscription->current_period_end;
        $lastNotificationId = (int) DB::table('user_notifications')->where('user_id', $technician->id)->max('id');

        $this->actingAs($technician)
            ->get('/technician/subscription')
            ->assertOk()
            ->assertSee('Cancel at period end');

        $this->actingAs($technician)
            ->post('/technician/subscription/cancel')
            ->assertRedirect('/technician/subscription');

        $this->actingAs($technician)
            ->getJson('/notifications/poll?after_id='.$lastNotificationId)
            ->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.title', 'Subscription cancellation scheduled')
            ->assertJsonPath('notifications.0.target_url', url('/technician/subscription'));

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'active',
            'cancel_at_period_end' => true,
            'current_period_end' => $periodEnd,
        ]);

        $this->actingAs($technician)
            ->get('/technician/schedule')
            ->assertOk();

        $this->actingAs($technician)
            ->get('/technician/subscription')
            ->assertOk()
            ->assertSee('Your subscription is set to end on')
            ->assertSee('Renew or extend your plan')
            ->assertDontSee('Cancel at period end');
    }

    public function test_cancellation_is_not_scheduled_while_a_renewal_payment_is_pending(): void
    {
        $admin = $this->createUser('admin');
        $technician = $this->createUser('technician');
        $firstPaymentId = $this->submitPayment($technician, 'GCASH-REF-CANCEL-2002');

        $this->actingAs($admin)
            ->post('/admin/subscriptions/'.$firstPaymentId.'/approve')
            ->assertRedirect('/admin/subscriptions');

        $this->submitPayment($technician, 'GCASH-REF-CANCEL-2003', 'yearly');

        $this->actingAs($technician)
            ->post('/technician/subscription/cancel')
            ->assertRedirect('/technician/subscription');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $technician->id,
            'status' => 'active',
            'cancel_at_period_end' => false,
        ]);

        $this->actingAs($technician)
            ->get('/technician/subscription')
            ->assertOk()
            ->assertSee('Resolve it before scheduling cancellation');
    }

    public function test_payment_reference_cannot_be_submitted_twice_and_admin_can_reject_pending_payment(): void
    {
        $admin = $this->createUser('admin');
        $firstTechnician = $this->createUser('technician');
        $secondTechnician = $this->createUser('technician', 'another-technician@example.com');
        DB::table('users')->where('id', $firstTechnician->id)->update(['technician_approved_at' => null]);
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
        $this->actingAs($firstTechnician)
            ->getJson('/notifications/poll?after_id=0')
            ->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.title', 'Subscription payment rejected')
            ->assertJsonPath('notifications.0.target_url', url('/technician/subscription'));
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

    public function test_plan_can_be_selected_before_gcash_details_are_configured(): void
    {
        config([
            'subscriptions.gcash.account_name' => '',
            'subscriptions.gcash.account_number' => '',
        ]);
        $technician = $this->createUser('technician');

        $this->actingAs($technician)
            ->get('/technician/subscription?plan=yearly')
            ->assertOk()
            ->assertSee('value="yearly" checked', false)
            ->assertSee('PayMongo checkout is not configured')
            ->assertDontSee('name="gcash_reference"');

        $this->actingAs($technician)
            ->get('/technician/subscription?plan[]=yearly')
            ->assertOk()
            ->assertSee('value="monthly" checked', false);
    }

    public function test_demo_checkout_simulates_payment_and_activates_subscription_without_a_gateway(): void
    {
        config(['services.payments.demo_mode' => true]);
        $technician = $this->createUser('technician');
        DB::table('users')->where('id', $technician->id)->update(['technician_approved_at' => null]);

        $this->actingAs($technician)
            ->get('/technician/subscription')
            ->assertOk()
            ->assertSee('Demo mode')
            ->assertSee('No real GCash payment will happen')
            ->assertSee('Continue to Demo Checkout');

        $checkoutResponse = $this->actingAs($technician)
            ->post('/technician/subscription/checkout', ['plan' => 'yearly']);

        $payment = DB::table('subscription_payments')->first();
        $paymentId = (int) $payment->id;
        $this->assertLessThanOrEqual(40, strlen($payment->gcash_reference));
        $this->assertMatchesRegularExpression('/\ADEMO-[A-F0-9]{32}\z/', $payment->gcash_reference);
        $checkoutResponse->assertRedirect('/technician/subscription/demo/'.$paymentId);

        $this->actingAs($technician)
            ->get('/technician/subscription/demo/'.$paymentId)
            ->assertOk()
            ->assertSee('Simulated checkout')
            ->assertSee('no money will be charged or transferred');

        $this->actingAs($technician)
            ->post('/technician/subscription/demo/'.$paymentId.'/complete')
            ->assertRedirect('/technician/subscription');

        $technician->refresh();
        $this->assertNotNull($technician->technician_approved_at);
        $this->assertDatabaseHas('subscription_payments', [
            'id' => $paymentId,
            'payment_provider' => 'demo',
            'status' => 'approved',
            'provider_payment_id' => 'demo-payment-'.$paymentId,
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'id' => $payment->subscription_id,
            'user_id' => $technician->id,
            'plan' => 'yearly',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $technician->id,
            'title' => 'Technician account approved',
            'target_url' => url('/technician'),
        ]);
    }

    public function test_demo_checkout_is_user_scoped_and_cannot_activate_in_production(): void
    {
        config(['services.payments.demo_mode' => true]);
        $technician = $this->createUser('technician');
        $otherTechnician = $this->createUser('technician', 'another-demo-technician@example.com');
        DB::table('users')->where('id', $technician->id)->update(['technician_approved_at' => null]);

        $checkoutResponse = $this->actingAs($technician)
            ->post('/technician/subscription/checkout', ['plan' => 'monthly']);
        $payment = DB::table('subscription_payments')->first();
        $paymentId = (int) $payment->id;
        $checkoutResponse->assertRedirect('/technician/subscription/demo/'.$paymentId);

        $this->actingAs($otherTechnician)
            ->get('/technician/subscription/demo/'.$paymentId)
            ->assertNotFound();

        $this->app->detectEnvironment(fn () => 'production');
        $this->assertFalse($this->app->make(TechnicianSubscriptionActivator::class)->approveFromDemo($paymentId));
        $this->app->detectEnvironment(fn () => 'testing');

        $technician->refresh();
        $this->assertNull($technician->technician_approved_at);
        $this->assertDatabaseHas('subscription_payments', ['id' => $paymentId, 'status' => 'pending']);
        $this->assertDatabaseHas('subscriptions', ['id' => $payment->subscription_id, 'status' => 'pending']);
    }

    public function test_demo_checkout_can_be_enabled_for_a_vercel_preview(): void
    {
        config([
            'services.payments.demo_mode' => true,
            'services.payments.vercel_environment' => 'preview',
        ]);
        $technician = $this->createUser('technician');

        $checkoutResponse = $this->actingAs($technician)
            ->post('/technician/subscription/checkout', ['plan' => 'monthly']);
        $payment = DB::table('subscription_payments')->first();
        $checkoutResponse->assertRedirect('/technician/subscription/demo/'.$payment->id);

        $this->app->detectEnvironment(fn () => 'production');
        $this->assertTrue($this->app->make(TechnicianSubscriptionActivator::class)->approveFromDemo((int) $payment->id));
        $this->app->detectEnvironment(fn () => 'testing');

        $this->assertDatabaseHas('subscription_payments', ['id' => $payment->id, 'status' => 'approved']);
    }

    public function test_demo_checkout_can_be_explicitly_enabled_in_production_for_a_presentation(): void
    {
        config([
            'services.payments.demo_mode' => true,
            'services.payments.allow_production_demo' => true,
        ]);
        $this->app->detectEnvironment(fn () => 'production');
        $technician = $this->createUser('technician');

        $this->actingAs($technician)
            ->get('/technician/subscription')
            ->assertOk()
            ->assertSee('Demo mode')
            ->assertSee('No real GCash payment will happen');

        $this->app->detectEnvironment(fn () => 'testing');
        $checkoutResponse = $this->actingAs($technician)
            ->post('/technician/subscription/checkout', ['plan' => 'monthly']);
        $paymentId = (int) DB::table('subscription_payments')->value('id');
        $checkoutResponse->assertRedirect('/technician/subscription/demo/'.$paymentId);

        $this->app->detectEnvironment(fn () => 'production');
        $this->assertTrue($this->app->make(TechnicianSubscriptionActivator::class)->approveFromDemo($paymentId));

        $this->app->detectEnvironment(fn () => 'testing');
        $this->assertDatabaseHas('subscription_payments', [
            'id' => $paymentId,
            'payment_provider' => 'demo',
            'status' => 'approved',
        ]);
    }

    public function test_technician_can_start_a_paymongo_gcash_checkout_for_the_selected_plan(): void
    {
        config([
            'services.paymongo.secret_key' => 'sk_test_example',
            'services.paymongo.webhook_secret' => 'whsec_example',
        ]);
        Http::fake([
            'https://api.paymongo.com/v2/checkout_sessions' => Http::response([
                'data' => [
                    'id' => 'cs_test_123',
                    'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/test-session'],
                ],
            ]),
        ]);

        $technician = $this->createUser('technician');

        $this->actingAs($technician)
            ->get('/technician/subscription?plan=yearly')
            ->assertOk()
            ->assertSee('Subscribe with GCash')
            ->assertSee('method="post"', false)
            ->assertSee('/technician/subscription/checkout')
            ->assertSee('value="yearly" checked', false);

        $this->actingAs($technician)
            ->post('/technician/subscription/checkout', ['plan' => 'yearly'])
            ->assertRedirect('https://checkout.paymongo.com/test-session');

        $payment = DB::table('subscription_payments')->first();
        $this->assertSame('paymongo', $payment->payment_provider);
        $this->assertSame('cs_test_123', $payment->provider_checkout_id);
        $this->assertDatabaseHas('subscriptions', [
            'id' => $payment->subscription_id,
            'plan' => 'yearly',
            'amount' => 5000,
            'status' => 'pending',
        ]);
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://api.paymongo.com/v2/checkout_sessions'
            && $request['data']['attributes']['payment_method_types'] === ['gcash']
            && $request['data']['attributes']['line_items'][0]['amount'] === 500000
            && $request->hasHeader('Idempotency-Key'));
    }

    public function test_checkout_retry_reuses_the_original_pending_plan_after_a_connection_error(): void
    {
        config([
            'services.paymongo.secret_key' => 'sk_test_example',
            'services.paymongo.webhook_secret' => 'whsec_example',
        ]);
        Http::fakeSequence('https://api.paymongo.com/v2/checkout_sessions')
            ->pushFailedConnection()
            ->push([
                'data' => [
                    'id' => 'cs_test_retry',
                    'attributes' => ['checkout_url' => 'https://checkout.paymongo.com/retry-session'],
                ],
            ]);
        $technician = $this->createUser('technician');

        $this->actingAs($technician)
            ->post('/technician/subscription/checkout', ['plan' => 'yearly'])
            ->assertRedirect('/technician/subscription?plan=yearly');

        $this->actingAs($technician)
            ->post('/technician/subscription/checkout', ['plan' => 'monthly'])
            ->assertRedirect('https://checkout.paymongo.com/retry-session');

        $payment = DB::table('subscription_payments')->first();
        $this->assertSame('pending', $payment->status);
        $this->assertSame('cs_test_retry', $payment->provider_checkout_id);
        $this->assertDatabaseCount('subscription_payments', 1);
        $this->assertDatabaseHas('subscriptions', [
            'id' => $payment->subscription_id,
            'plan' => 'yearly',
            'amount' => 5000,
        ]);
        Http::assertSent(fn (ClientRequest $request) => $request['data']['attributes']['line_items'][0]['amount'] === 500000);
    }

    public function test_valid_paymongo_webhook_approves_the_technician_and_subscription_once(): void
    {
        config([
            'services.paymongo.secret_key' => 'sk_test_example',
            'services.paymongo.webhook_secret' => 'whsec_example',
        ]);
        $technician = $this->createUser('technician');
        $paymentId = $this->createPayMongoPayment($technician, 'SUB-TEST-1001', 'cs_test_1001');

        $this->postSignedPayMongoWebhook('SUB-TEST-1001', 'cs_test_1001')
            ->assertOk()
            ->assertExactJson(['received' => true]);
        $this->postSignedPayMongoWebhook('SUB-TEST-1001', 'cs_test_1001')
            ->assertOk();

        $technician->refresh();
        $this->assertNotNull($technician->technician_approved_at);
        $this->assertDatabaseHas('subscription_payments', [
            'id' => $paymentId,
            'status' => 'approved',
            'provider_payment_id' => 'pay_test_1001',
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $technician->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseCount('paymongo_webhook_events', 1);
    }

    public function test_invalid_or_mismatched_paymongo_webhooks_do_not_approve_payments(): void
    {
        config([
            'services.paymongo.secret_key' => 'sk_test_example',
            'services.paymongo.webhook_secret' => 'whsec_example',
        ]);
        $technician = $this->createUser('technician');
        $paymentId = $this->createPayMongoPayment($technician, 'SUB-TEST-2002', 'cs_test_2002');

        $this->postSignedPayMongoWebhook('SUB-TEST-2002', 'cs_test_2002', 50000, true)
            ->assertUnauthorized();
        $this->postSignedPayMongoWebhook('SUB-TEST-2002', 'cs_test_2002', 49900)
            ->assertOk();

        $technician->refresh();
        $this->assertNull($technician->technician_approved_at);
        $this->assertDatabaseHas('subscription_payments', [
            'id' => $paymentId,
            'status' => 'pending',
            'provider_payment_id' => null,
        ]);
        $this->assertDatabaseMissing('subscriptions', [
            'user_id' => $technician->id,
            'status' => 'active',
        ]);
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

    private function createPayMongoPayment(User $technician, string $reference, string $checkoutId): int
    {
        DB::table('users')->where('id', $technician->id)->update(['technician_approved_at' => null]);

        $subscriptionId = DB::table('subscriptions')->insertGetId([
            'user_id' => $technician->id,
            'plan' => 'monthly',
            'amount' => 500,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('subscription_payments')->insertGetId([
            'subscription_id' => $subscriptionId,
            'user_id' => $technician->id,
            'gcash_reference' => $reference,
            'proof_path' => '',
            'status' => 'pending',
            'payment_provider' => 'paymongo',
            'provider_checkout_id' => $checkoutId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function postSignedPayMongoWebhook(
        string $reference,
        string $checkoutId,
        int $amount = 50000,
        bool $tamperSignature = false
    ) {
        $payload = [
            'data' => [
                'type' => 'checkout_session.payment.paid',
                'resource' => 'checkout_session',
                'livemode' => false,
                'data' => [
                    'id' => $checkoutId,
                    'type' => 'checkout_session',
                    'attributes' => [
                        'reference_number' => $reference,
                        'payments' => [[
                            'id' => 'pay_test_1001',
                            'attributes' => [
                                'status' => 'paid',
                                'currency' => 'PHP',
                                'amount' => $amount,
                                'source' => ['type' => 'gcash'],
                            ],
                        ]],
                    ],
                ],
            ],
        ];
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = '1770000000';
        $signature = hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_example');
        if ($tamperSignature) {
            $signature = str_repeat('0', 64);
        }

        return $this->call(
            'POST',
            '/webhooks/paymongo',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_PAYMONGO_SIGNATURE' => 't='.$timestamp.',te='.$signature,
            ],
            $body
        );
    }

    private function createUser(string $role, ?string $email = null): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => ucfirst($role),
            'email' => $email ?? $role.'@example.com',
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
}
