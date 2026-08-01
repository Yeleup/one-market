<?php

use App\Models\Client;
use App\Models\Institution;
use App\Models\Language;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function renderOrderPdfView(Order $order): string
{
    return view('pdf.orders.show', [
        'order' => $order->load([
            'client',
            'createdByUser',
            'items',
            'institution' => fn ($query) => $query->withLocalizedName(),
        ]),
    ])->render();
}

beforeEach(function (): void {
    Language::factory()->default()->create(['code' => 'ru']);
    app()->setLocale('ru');
});

it('omits the source, created by and placed at rows', function (): void {
    $order = Order::factory()
        ->for(Client::factory())
        ->for(Institution::factory())
        ->create(['created_by_user_id' => User::factory()->create()->getKey()]);

    $html = renderOrderPdfView($order);

    expect($html)
        ->not->toContain(__('admin.common.fields.source'))
        ->not->toContain(__('admin.common.fields.created_by'))
        ->not->toContain(__('admin.common.fields.placed_at'));
});

it('prints the created at timestamp in the display timezone, not utc', function (): void {
    config(['app.display_timezone' => 'Asia/Almaty']);

    $order = Order::factory()
        ->for(Client::factory())
        ->for(Institution::factory())
        ->create(['created_at' => Carbon::parse('2026-06-29 06:44:54', 'UTC')]);

    $html = renderOrderPdfView($order);

    expect($html)->toContain('2026-06-29 11:44')
        ->and($html)->not->toContain('2026-06-29 06:44');
});

it('renders the configured supplier name and bin', function (): void {
    config([
        'company.supplier.name' => 'TOO Food Alem',
        'company.supplier.bin' => '250640027310',
    ]);

    $order = Order::factory()
        ->for(Client::factory())
        ->for(Institution::factory())
        ->create();

    $html = renderOrderPdfView($order);

    expect($html)
        ->toContain(__('admin.common.fields.supplier'))
        ->toContain('TOO Food Alem')
        ->toContain('250640027310');
});

it('renders the released by and received by signature lines', function (): void {
    $order = Order::factory()
        ->for(Client::factory())
        ->for(Institution::factory())
        ->create();

    $html = renderOrderPdfView($order);

    expect($html)
        ->toContain(__('admin.resources.order.pdf.released_by'))
        ->toContain(__('admin.resources.order.pdf.received_by'));
});

it('keeps the order items and totals on a single compact table', function (): void {
    $order = Order::factory()
        ->for(Client::factory())
        ->for(Institution::factory())
        ->create(['total_bonus' => 300, 'total_weight_grams' => 1500]);

    OrderItem::query()->create([
        'order_id' => $order->getKey(),
        'product_id' => null,
        'product_name' => 'Молоко',
        'product_image' => null,
        'price_bonus' => 100,
        'weight_grams' => 500,
        'quantity' => 3,
        'line_total_bonus' => 300,
        'line_total_weight_grams' => 1500,
    ]);

    $html = renderOrderPdfView($order);

    $footer = substr($html, (int) strpos($html, '<tfoot>'));

    expect($html)->toContain('Молоко')
        ->and($footer)->toContain(__('admin.common.fields.total_bonus'))
        ->and($footer)->toContain(__('admin.common.fields.total_weight'))
        ->and($footer)->toContain(__('admin.common.fields.bonus_reserved'));
});
