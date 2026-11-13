<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class AdminSubscriptionController extends Controller
{
    public function index()
    {
        $payments = DB::table('subscription_payments')
            ->join('subscriptions', 'subscriptions.id', '=', 'subscription_payments.subscription_id')
            ->join('users', 'users.id', '=', 'subscription_payments.user_id')
            ->leftJoin('users as reviewers', 'reviewers.id', '=', 'subscription_payments.reviewed_by')
            ->select(
                'subscription_payments.*',
                'subscriptions.plan',
                'subscriptions.amount',
                'subscriptions.status as subscription_status',
                'subscriptions.current_period_start',
                'subscriptions.current_period_end',
                'users.name as technician_name',
                'users.email as technician_email',
                'reviewers.name as reviewer_name'
            )
            ->orderByRaw("CASE WHEN subscription_payments.status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('subscription_payments.created_at')
            ->limit(100)
            ->get();

        return view('admin.subscriptions', [
            'title' => 'Technician subscriptions',
            'payments' => $payments,
            'pendingCount' => DB::table('subscription_payments')->where('status', 'pending')->count(),
            'approvedIncome' => DB::table('subscription_payments')
                ->join('subscriptions', 'subscriptions.id', '=', 'subscription_payments.subscription_id')
                ->where('subscription_payments.status', 'approved')
                ->sum('subscriptions.amount'),
        ]);
    }

    public function proof(int $paymentId)
    {
        $payment = DB::table('subscription_payments')->where('id', $paymentId)->first();

        if (! $payment || ! $payment->proof_path) {
            $this->notFound('That payment proof could not be found.');
        }

        $extension = pathinfo($payment->proof_path, PATHINFO_EXTENSION);

        return Storage::disk(config('subscriptions.proof_disk'))
            ->download($payment->proof_path, 'gcash-proof-'.$paymentId.'.'.$extension);
    }

    public function approve(int $paymentId)
    {
        $outcome = DB::transaction(function () use ($paymentId) {
            $payment = DB::table('subscription_payments')
                ->where('id', $paymentId)
                ->lockForUpdate()
                ->first();

            if (! $payment || $payment->status !== 'pending') {
                return false;
            }

            $subscription = DB::table('subscriptions')
                ->where('id', $payment->subscription_id)
                ->lockForUpdate()
                ->first();

            if (! $subscription || $subscription->status !== 'pending') {
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
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        });

        if (! $outcome) {
            $this->flash('error', 'That payment is no longer pending review.');

            return redirect('/admin/subscriptions');
        }

        $this->log('approve_subscription', 'Approved technician subscription payment #'.$paymentId);
        $this->flash('success', 'Payment approved and technician access enabled.');

        return redirect('/admin/subscriptions');
    }

    public function reject(int $paymentId)
    {
        $outcome = DB::transaction(function () use ($paymentId) {
            $payment = DB::table('subscription_payments')
                ->where('id', $paymentId)
                ->lockForUpdate()
                ->first();

            if (! $payment || $payment->status !== 'pending') {
                return false;
            }

            DB::table('subscription_payments')->where('id', $payment->id)->update([
                'status' => 'rejected',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_note' => mb_substr($this->input('review_note'), 0, 255) ?: null,
                'updated_at' => now(),
            ]);
            DB::table('subscriptions')->where('id', $payment->subscription_id)
                ->where('status', 'pending')
                ->update(['status' => 'rejected', 'updated_at' => now()]);

            return true;
        });

        if (! $outcome) {
            $this->flash('error', 'That payment is no longer pending review.');

            return redirect('/admin/subscriptions');
        }

        $this->log('reject_subscription', 'Rejected technician subscription payment #'.$paymentId);
        $this->flash('info', 'Payment rejected.');

        return redirect('/admin/subscriptions');
    }
}
