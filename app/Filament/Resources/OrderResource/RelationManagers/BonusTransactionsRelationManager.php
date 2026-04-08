<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Enums\BonusTransactionType;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BonusTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'bonusTransactions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relation_managers.bonus_transactions');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('created_at')->label(__('admin.common.fields.created_at'))->since()->sortable(),
                TextColumn::make('type')->label(__('admin.common.fields.type'))->badge(),
                TextColumn::make('amount')->label(__('admin.common.fields.amount'))->sortable(),
                TextColumn::make('balance_delta')->label(__('admin.common.fields.balance_delta'))->sortable(),
                TextColumn::make('reserved_delta')->label(__('admin.common.fields.reserved_delta'))->sortable(),
                TextColumn::make('performedByUser.name')->label(__('admin.common.fields.performed_by')),
                TextColumn::make('comment')->label(__('admin.common.fields.comment'))->limit(50),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(BonusTransactionType::class),
            ])
            ->defaultSort('id', 'desc');
    }
}
