<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfClientAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('client')->check()) {
            return redirect()->route('storefront.dashboard');
        }

        return $next($request);
    }
}
