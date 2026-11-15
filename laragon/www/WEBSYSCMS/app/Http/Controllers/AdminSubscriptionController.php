<?php

namespace App\Http\Controllers;

use App\Services\TechnicianSubscriptionActivator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
                'users.phone as technician_phone',
                'users.specialty as technician_specialty',
                'users.technician_approved_at',
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

    public function approve(int $paymentId, TechnicianSubscriptionActivator $activator)
    {
        $outcome = $activator->approveManually($paymentId, (int) auth()->id());

        if (! $outcome) {
            $this->flash('error', 'That payment is no longer pending review.');

            return redirect('/admin/subscriptions');
        }

        $this->log('approve_subscription', 'Approved technician account and subscription payment #'.$paymentId);
        $this->flash('success', 'Payment verified, subscription activated, and technician account approved.');

        return redirect('/admin/subscriptions');
    }

    public function reject(int $paymentId)
    {
        $outcome = DB::transaction(function () use ($paymentId) {
            $payment = DB::table('subscription_payments')
                ->where('id', $paymentId)
                ->lockForUpdate()
                ->first();

            if (! $payment || $payment->status !== 'pending' || $payment->payment_provider !== 'manual') {
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

            DB::table('user_notifications')->insert([
                'user_id' => $payment->user_id,
                'title' => 'Subscription payment rejected',
                'body' => 'Your subscription payment was rejected. Please review the payment details and contact an administrator if you need help.',
                'type' => 'info',
                'is_read' => false,
                'target_url' => url('/technician/subscription'),
                'created_at' => now(),
            ]);

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
