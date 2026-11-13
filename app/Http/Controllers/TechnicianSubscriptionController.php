<?php

namespace App\Http\Controllers;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TechnicianSubscriptionController extends Controller
{
    public function show()
    {
        $userId = (int) auth()->id();
        $subscription = DB::table('subscriptions')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where('current_period_end', '>', now())
            ->orderByDesc('current_period_end')
            ->first();

        $pendingPayment = DB::table('subscription_payments')
            ->join('subscriptions', 'subscriptions.id', '=', 'subscription_payments.subscription_id')
            ->where('subscription_payments.user_id', $userId)
            ->where('subscription_payments.status', 'pending')
            ->select('subscription_payments.*', 'subscriptions.plan')
            ->latest('subscription_payments.id')
            ->first();

        $latestPayment = DB::table('subscription_payments')
            ->join('subscriptions', 'subscriptions.id', '=', 'subscription_payments.subscription_id')
            ->where('subscription_payments.user_id', $userId)
            ->select('subscription_payments.*', 'subscriptions.plan')
            ->latest('subscription_payments.id')
            ->first();

        return view('technician.subscription', [
            'title' => 'Technician subscription',
            'subscription' => $subscription,
            'pendingPayment' => $pendingPayment,
            'latestPayment' => $latestPayment,
            'prices' => config('subscriptions.prices'),
            'gcash' => config('subscriptions.gcash'),
        ]);
    }

    public function submitPayment()
    {
        $plans = config('subscriptions.prices');
        $plan = $this->input('plan');
        $reference = strtoupper($this->input('gcash_reference'));
        $errors = [];

        if (! array_key_exists($plan, $plans)) {
            $errors['plan'] = 'Choose a valid subscription plan.';
        }

        if (mb_strlen($reference) < 6 || mb_strlen($reference) > 40) {
            $errors['gcash_reference'] = 'Enter the GCash transaction reference (6 to 40 characters).';
        } elseif (DB::table('subscription_payments')->where('gcash_reference', $reference)->exists()) {
            $errors['gcash_reference'] = 'That GCash reference has already been submitted.';
        }

        if (! config('subscriptions.gcash.account_name') || ! config('subscriptions.gcash.account_number')) {
            $errors['gcash_reference'] = 'GCash payment details are not configured yet. Contact the administrator.';
        }

        $proofValidator = validator(request()->all(), [
            'proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        if ($proofValidator->fails()) {
            $errors['proof'] = $proofValidator->errors()->first('proof');
        }

        if ($errors) {
            return $this->backWithErrors($errors, '/technician/subscription');
        }

        if (DB::table('subscription_payments')->where('user_id', auth()->id())->where('status', 'pending')->exists()) {
            $this->flash('info', 'You already have a payment awaiting review.');

            return redirect('/technician/subscription');
        }

        $proofDisk = config('subscriptions.proof_disk');
        $proofFile = request()->file('proof');
        $proofPath = $proofFile->store('subscription-proofs/'.auth()->id(), [
            'disk' => $proofDisk,
            'visibility' => 'private',
        ]);

        if (! $proofPath) {
            return $this->backWithErrors(
                ['proof' => 'The receipt image could not be saved. Please try again or contact the administrator.'],
                '/technician/subscription'
            );
        }

        $paymentSaved = false;
        try {
            $paymentSaved = DB::transaction(function () use ($plan, $reference, $plans, $proofPath) {
                DB::table('users')->where('id', auth()->id())->lockForUpdate()->first();

                $hasPendingPayment = DB::table('subscription_payments')
                    ->where('user_id', auth()->id())
                    ->where('status', 'pending')
                    ->exists();

                if ($hasPendingPayment) {
                    return;
                }

                $subscriptionId = DB::table('subscriptions')->insertGetId([
                    'user_id' => auth()->id(),
                    'plan' => $plan,
                    'amount' => $plans[$plan],
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('subscription_payments')->insert([
                    'subscription_id' => $subscriptionId,
                    'user_id' => auth()->id(),
                    'gcash_reference' => $reference,
                    'proof_path' => $proofPath,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return true;
            });
        } catch (UniqueConstraintViolationException) {
            return $this->backWithErrors(
                ['gcash_reference' => 'That GCash reference has already been submitted.'],
                '/technician/subscription'
            );
        } finally {
            if (! $paymentSaved && ! Storage::disk($proofDisk)->delete($proofPath)) {
                logger()->warning('Could not remove an unreferenced subscription payment proof.', [
                    'path' => $proofPath,
                ]);
            }
        }

        if ($paymentSaved) {
            $this->flash('success', 'Payment reference submitted. Technician access will be enabled after the administrator verifies your GCash payment.');
        } else {
            $this->flash('info', 'You already have a payment awaiting review.');
        }

        return redirect('/technician/subscription');
    }
}
