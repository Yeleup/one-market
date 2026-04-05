<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use App\Enums\BonusTransactionType;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BonusTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'bonusTransactions';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('amount')->sortable(),
                TextColumn::make('balance_delta')->sortable(),
                TextColumn::make('reserved_delta')->sortable(),
                TextColumn::make('order.id')->label('Order #'),
                TextColumn::make('performedByUser.name')->label('Performed by'),
                TextColumn::make('comment')->limit(50),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(BonusTransactionType::class),
            ])
            ->defaultSort('id', 'desc');
    }
}
