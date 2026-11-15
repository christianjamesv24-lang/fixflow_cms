<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TechnicianSubscriptionActivator
{
    public function approveManually(int $paymentId, int $reviewerId): bool
    {
        return $this->activate($paymentId, 'manual', $reviewerId);
    }

    public function approveFromPayMongo(int $paymentId, string $providerPaymentId): bool
    {
        return $this->activate($paymentId, 'paymongo', null, $providerPaymentId);
    }

    public function approveFromDemo(int $paymentId): bool
    {
        if (! config('services.payments.demo_mode')
            || (! app()->environment(['local', 'testing'])
                && config('services.payments.vercel_environment') !== 'preview'
                && (! app()->environment('production')
                    || ! config('services.payments.allow_production_demo')))) {
            return false;
        }

        return $this->activate($paymentId, 'demo', null, 'demo-payment-'.$paymentId);
    }

    private function activate(
        int $paymentId,
        string $provider,
        ?int $reviewerId,
        ?string $providerPaymentId = null
    ): bool {
        return DB::transaction(function () use ($paymentId, $provider, $reviewerId, $providerPaymentId) {
            $payment = DB::table('subscription_payments')
                ->where('id', $paymentId)
                ->lockForUpdate()
                ->first();

            if (! $payment || $payment->status !== 'pending' || $payment->payment_provider !== $provider) {
                return false;
            }

            $subscription = DB::table('subscriptions')
                ->where('id', $payment->subscription_id)
                ->lockForUpdate()
                ->first();

            $technician = DB::table('users')
                ->where('id', $payment->user_id)
                ->lockForUpdate()
                ->first();

            if (! $subscription || $subscription->status !== 'pending'
                || ! $technician || $technician->role !== 'technician') {
                return false;
            }

            $existingEnd = DB::table('subscriptions')
                ->where('user_id', $subscription->user_id)
                ->where('status', 'active')
                ->where('current_period_end', '>', now())
                ->max('current_period_end');

            $periodStart = $existingEnd ? Carbon::parse($existingEnd) : now();
            $periodEnd = $subscription->plan === 'yearly'
                ? $periodStart->copy()->addYear()
                : $periodStart->copy()->addMonth();

            DB::table('subscriptions')->where('id', $subscription->id)->update([
                'status' => 'active',
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'updated_at' => now(),
            ]);

            DB::table('subscription_payments')->where('id', $payment->id)->update([
                'status' => 'approved',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
                'paid_at' => now(),
                'provider_payment_id' => $providerPaymentId,
                'updated_at' => now(),
            ]);

            DB::table('users')->where('id', $technician->id)->update([
                'technician_approved_at' => $technician->technician_approved_at ?? now(),
                'updated_at' => now(),
            ]);

            DB::table('user_notifications')->insert([
                'user_id' => $technician->id,
                'title' => 'Technician account approved',
                'body' => 'Your technician account and subscription have been approved. You can now access technician features.',
                'type' => 'info',
                'is_read' => false,
                'target_url' => url('/technician'),
                'created_at' => now(),
            ]);

            return true;
        });
    }
}
