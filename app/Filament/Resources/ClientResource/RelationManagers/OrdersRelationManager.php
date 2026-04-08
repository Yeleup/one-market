<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use App\Enums\OrderStatus;
use App\Enums\RecipientType;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relation_managers.orders');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')->label(__('admin.common.fields.id'))->sortable(),
                TextColumn::make('status')->label(__('admin.common.fields.status'))->badge(),
                TextColumn::make('recipient_type')->label(__('admin.common.fields.recipient_type'))->badge(),
                TextColumn::make('institution.id')->label(__('admin.common.fields.institution')),
                TextColumn::make('total_bonus')->label(__('admin.common.fields.total_bonus'))->sortable(),
                TextColumn::make('total_weight_grams')->label(__('admin.common.fields.total_weight'))->suffix(' g')->sortable(),
                TextColumn::make('placed_at')->label(__('admin.common.fields.placed_at'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(OrderStatus::class),
                SelectFilter::make('recipient_type')
                    ->options(RecipientType::class),
            ])
            ->recordUrl(fn (Order $record): string => OrderResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('id', 'desc');
    }
}
