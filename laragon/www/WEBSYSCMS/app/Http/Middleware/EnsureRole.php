<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! auth()->check()) {
            session()->put('url.intended', $request->fullUrl());
            flash('error', 'Please sign in to continue.');

            return redirect('/login');
        }

        $user = auth()->user();

        if ($user->status !== 'active') {
            auth()->logout();
            session()->invalidate();
            session()->regenerateToken();
            flash('error', 'Your account is inactive. Please contact support.');

            return redirect('/login');
        }

        if ($roles !== [] && ! in_array($user->role, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
