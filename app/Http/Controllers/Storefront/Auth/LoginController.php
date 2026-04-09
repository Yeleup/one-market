<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Auth\LoginRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function showLoginForm(): Response
    {
        return Inertia::render('Storefront/Auth/Login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        if (! Auth::guard('client')->attempt($credentials)) {
            return back()->withErrors([
                'bin' => 'Неверный БИН или пароль.',
            ]);
        }

        /** @var Client $client */
        $client = Auth::guard('client')->user();

        if (! $client->is_active) {
            Auth::guard('client')->logout();

            return back()->withErrors([
                'bin' => 'Ваш аккаунт деактивирован.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('storefront.dashboard'));
    }
}
