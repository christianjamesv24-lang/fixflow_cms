<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTechnicianApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user->role === 'technician' && $user->technician_approved_at === null) {
            flash('warning', 'Your technician account and subscription are awaiting admin approval.');

            return redirect('/technician/subscription');
        }

        return $next($request);
    }
}
