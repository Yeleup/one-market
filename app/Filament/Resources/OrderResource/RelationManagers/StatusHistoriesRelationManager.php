<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StatusHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistories';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relation_managers.status_histories');
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
                TextColumn::make('created_at')->label(__('admin.common.fields.created_at'))->dateTime()->sortable(),
                TextColumn::make('from_status')->label(__('admin.common.fields.from_status'))->badge(),
                TextColumn::make('to_status')->label(__('admin.common.fields.to_status'))->badge(),
                TextColumn::make('changedByUser.name')->label(__('admin.common.fields.changed_by')),
                TextColumn::make('comment')->label(__('admin.common.fields.comment'))->limit(50),
            ])
            ->defaultSort('id', 'desc');
    }
}
