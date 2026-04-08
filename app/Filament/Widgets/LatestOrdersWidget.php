<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrdersWidget extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('admin.dashboard.latest_orders'))
            ->query(
                Order::query()
                    ->with([
                        'client',
                        'institution' => fn ($query) => $query->withLocalizedName(),
                    ])
                    ->latest('created_at'),
            )
            ->columns([
                TextColumn::make('id')
                    ->label(__('admin.common.fields.id'))
                    ->sortable(),
                TextColumn::make('client.full_name')
                    ->label(__('admin.common.fields.client')),
                TextColumn::make('institution.localized_name')
                    ->label(__('admin.common.fields.institution')),
                TextColumn::make('status')
                    ->label(__('admin.common.fields.status'))
                    ->badge(),
                TextColumn::make('total_bonus')
                    ->label(__('admin.common.fields.total_bonus'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.common.fields.created_at'))
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(10)
            ->paginated([10])
            ->recordUrl(fn (Order $record): string => OrderResource::getUrl('edit', ['record' => $record]));
    }
}
