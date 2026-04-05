<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StatusHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistories';

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
                TextColumn::make('from_status')->badge(),
                TextColumn::make('to_status')->badge(),
                TextColumn::make('changedByUser.name')->label('Changed by'),
                TextColumn::make('comment')->limit(50),
            ])
            ->defaultSort('id', 'desc');
    }
}
