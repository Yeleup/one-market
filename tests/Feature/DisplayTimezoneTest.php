<?php

use App\Filament\Widgets\DashboardOverviewWidget;
use App\Models\Client;
use App\Models\Institution;
use App\Models\Order;
use App\Models\User;
use App\Support\Dates\DisplayDate;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['app.display_timezone' => 'Asia/Almaty']);
});

it('keeps storing timestamps in utc', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-06-29 06:44:54', 'UTC'));

    $order = Order::factory()
        ->for(Client::factory())
        ->for(Institution::factory())
        ->create();

    $stored = DB::table('orders')->where('id', $order->getKey())->value('created_at');

    expect($stored)->toStartWith('2026-06-29 06:44');

    Carbon::setTestNow();
});

it('hands filament the display timezone', function (): void {
    expect(FilamentTimezone::get())->toBe('Asia/Almaty');
});

it('counts today orders by the display timezone day, not the utc day', function (): void {
    /* 21:30 UTC is already 02:30 on 30 June in Almaty. */
    Carbon::setTestNow(Carbon::parse('2026-06-29 21:30:00', 'UTC'));

    $client = Client::factory()->create();
    $institution = Institution::factory()->create();

    /* 18:00 UTC on 29 June = 23:00 Almaty the same day — yesterday for the operator. */
    Order::factory()->for($client)->for($institution)->create([
        'created_at' => Carbon::parse('2026-06-29 18:00:00', 'UTC'),
    ]);

    /* 20:00 UTC on 29 June = 01:00 Almaty on 30 June — today for the operator. */
    Order::factory()->for($client)->for($institution)->create([
        'created_at' => Carbon::parse('2026-06-29 20:00:00', 'UTC'),
    ]);

    $startOfToday = DisplayDate::startOfToday();

    $todayCount = Order::query()
        ->where('created_at', '>=', $startOfToday)
        ->where('created_at', '<', $startOfToday->copy()->addDay())
        ->count();

    expect($todayCount)->toBe(1);

    Carbon::setTestNow();
});

it('shows the dashboard today counter using the display timezone', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-06-29 21:30:00', 'UTC'));

    $client = Client::factory()->create();
    $institution = Institution::factory()->create();

    Order::factory()->for($client)->for($institution)->create([
        'created_at' => Carbon::parse('2026-06-29 18:00:00', 'UTC'),
    ]);
    Order::factory()->for($client)->for($institution)->create([
        'created_at' => Carbon::parse('2026-06-29 20:00:00', 'UTC'),
    ]);

    $this->actingAs(User::factory()->create());

    Livewire::test(DashboardOverviewWidget::class)
        ->assertSee(__('admin.dashboard.today', ['count' => 1]));

    Carbon::setTestNow();
});

it('renders storefront order timestamps in the display timezone', function (): void {
    $client = Client::factory()->create();
    $institution = Institution::factory()->create();

    $order = Order::factory()->for($client)->for($institution)->create([
        'placed_at' => Carbon::parse('2026-06-29 06:44:54', 'UTC'),
    ]);

    $this->actingAs($client, 'client')
        ->get(route('storefront.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('order.placed_at', '29.06.2026 11:44'));
});
