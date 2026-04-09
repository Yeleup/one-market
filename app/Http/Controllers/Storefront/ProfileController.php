<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ChangePasswordRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function index(): Response
    {
        /** @var Client $client */
        $client = Auth::guard('client')->user();

        $client->load(['institution' => fn ($q) => $q->withLocalizedName()]);

        return Inertia::render('Storefront/Profile', [
            'client' => [
                'first_name' => $client->first_name,
                'last_name' => $client->last_name,
                'bin' => $client->bin,
                'bonus_balance' => $client->bonus_balance,
                'bonus_reserved' => $client->bonus_reserved,
                'available_bonuses' => $client->bonus_balance - $client->bonus_reserved,
                'institution' => $client->institution ? [
                    'id' => $client->institution->id,
                    'name' => $client->institution->localized_name,
                ] : null,
            ],
        ]);
    }

    public function updatePassword(ChangePasswordRequest $request): RedirectResponse
    {
        /** @var Client $client */
        $client = Auth::guard('client')->user();

        $client->update([
            'password' => $request->validated('password'),
        ]);

        return back()->with('success', 'Пароль успешно изменён.');
    }
}
