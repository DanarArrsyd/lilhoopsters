<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegistrationStatusMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->registration_status === 'pending') {
            return redirect()->route('pending');
        }

        if ($user->registration_status === 'rejected') {
            abort(403, 'Your registration has been rejected. Please contact admin for more information.');
        }

        if (! $user->is_active) {
            abort(403, 'Your account has been deactivated. Please contact admin.');
        }

        return $next($request);
    }
}
