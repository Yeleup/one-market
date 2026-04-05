<?php

namespace App\Filament\Resources;

use App\Enums\BonusTransactionType;
use App\Filament\Resources\BonusTransactionResource\Pages;
use App\Models\BonusTransaction;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class BonusTransactionResource extends Resource
{
    protected static ?string $model = BonusTransaction::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_id')
                    ->relationship(name: 'client', titleAttribute: 'login')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('order_id')
                    ->relationship(name: 'order', titleAttribute: 'id')
                    ->searchable()
                    ->preload(),
                Select::make('performed_by_user_id')
                    ->relationship(name: 'performedByUser', titleAttribute: 'name')
                    ->searchable()
                    ->preload(),
                Select::make('type')
                    ->options(BonusTransactionType::class)
                    ->required(),
                TextInput::make('amount')
                    ->numeric()
                    ->required(),
                TextInput::make('balance_delta')
                    ->numeric()
                    ->required(),
                TextInput::make('reserved_delta')
                    ->numeric()
                    ->default(0),
                Textarea::make('comment'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('client.login')->searchable(),
                TextColumn::make('order.id')->label('Order #'),
                TextColumn::make('type')->badge(),
                TextColumn::make('amount')->sortable(),
                TextColumn::make('balance_delta')->sortable(),
                TextColumn::make('reserved_delta')->sortable(),
                TextColumn::make('performedByUser.name')->label('Performed by'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options(BonusTransactionType::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBonusTransactions::route('/'),
            'create' => Pages\CreateBonusTransaction::route('/create'),
            'edit' => Pages\EditBonusTransaction::route('/{record}/edit'),
        ];
    }
}
