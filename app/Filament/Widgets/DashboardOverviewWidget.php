<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Institution;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class DashboardOverviewWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = null;

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $todayOrdersCount = Order::query()
            ->whereDate('created_at', today())
            ->count();

        $activeClientsCount = Client::query()
            ->where('is_active', true)
            ->count();

        $activeProductsCount = Product::query()
            ->where('is_active', true)
            ->count();

        $activeInstitutionsCount = Institution::query()
            ->where('is_active', true)
            ->count();

        return [
            Stat::make(
                __('admin.resources.order.plural_model_label'),
                Number::format(Order::query()->count()),
            )
                ->description(__('admin.dashboard.today', ['count' => Number::format($todayOrdersCount)]))
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('primary')
                ->icon('heroicon-o-clipboard-document-list'),
            Stat::make(
                __('admin.dashboard.orders_in_processing'),
                Number::format(Order::query()->where('status', OrderStatus::Processing)->count()),
            )
                ->description(__('admin.dashboard.need_attention'))
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color('warning')
                ->icon('heroicon-o-arrow-path'),
            Stat::make(
                __('admin.resources.client.plural_model_label'),
                Number::format(Client::query()->count()),
            )
                ->description(__('admin.dashboard.active', ['count' => Number::format($activeClientsCount)]))
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success')
                ->icon('heroicon-o-user-group'),
            Stat::make(
                __('admin.resources.product.plural_model_label'),
                Number::format(Product::query()->count()),
            )
                ->description(__('admin.dashboard.active', ['count' => Number::format($activeProductsCount)]))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('info')
                ->icon('heroicon-o-shopping-bag'),
            Stat::make(
                __('admin.resources.institution.plural_model_label'),
                Number::format(Institution::query()->count()),
            )
                ->description(__('admin.dashboard.active', ['count' => Number::format($activeInstitutionsCount)]))
                ->descriptionIcon('heroicon-o-building-office')
                ->color('gray')
                ->icon('heroicon-o-building-office'),
        ];
    }
}
