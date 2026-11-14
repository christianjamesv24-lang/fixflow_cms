<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user->role !== 'technician') {
            return $next($request);
        }

        $hasActiveSubscription = DB::table('subscriptions')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('current_period_end', '>', now())
            ->exists();

        if (! $hasActiveSubscription) {
            flash('warning', 'Choose a plan and submit your GCash payment reference to restore technician access.');

            return redirect('/technician/subscription');
        }

        return $next($request);
    }
}
