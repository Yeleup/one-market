<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientIsAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('client');

        if (! $guard->check()) {
            return redirect()->route('storefront.login');
        }

        /** @var Client $client */
        $client = $guard->user();

        if (! $client->is_active) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('storefront.login')
                ->with('error', 'Ваш аккаунт деактивирован.');
        }

        return $next($request);
    }
}
