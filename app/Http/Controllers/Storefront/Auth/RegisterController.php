<?php

namespace App\Http\Controllers\Storefront\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Auth\RegisterRequest;
use App\Models\Client;
use App\Models\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function showRegistrationForm(): Response
    {
        return Inertia::render('Storefront/Auth/Register', [
            'institutions' => Institution::query()
                ->where('is_active', true)
                ->withLocalizedName()
                ->orderBy('id')
                ->get()
                ->map(fn (Institution $institution) => [
                    'id' => $institution->id,
                    'name' => $institution->localized_name,
                ]),
        ]);
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $client = Client::query()->create([
            ...$request->validated(),
            'bonus_balance' => 0,
            'bonus_reserved' => 0,
            'is_active' => true,
        ]);

        Auth::guard('client')->login($client);

        $request->session()->regenerate();

        return redirect()->route('storefront.dashboard');
    }
}
