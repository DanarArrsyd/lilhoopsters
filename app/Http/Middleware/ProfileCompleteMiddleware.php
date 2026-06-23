<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProfileCompleteMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->hasRole('parent')
            && $user->google_id
            && empty($user->whatsapp_number)
            && ! $request->routeIs('parent.complete-profile')
            && ! $request->routeIs('logout')
        ) {
            return redirect()->route('parent.complete-profile');
        }

        return $next($request);
    }
}
