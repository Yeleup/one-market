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

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.bonus_transaction.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.bonus_transaction.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.bonus_transaction.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_id')
                    ->label(__('admin.common.fields.client'))
                    ->relationship(name: 'client', titleAttribute: 'login')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('order_id')
                    ->label(__('admin.common.fields.order_number'))
                    ->relationship(name: 'order', titleAttribute: 'id')
                    ->searchable()
                    ->preload(),
                Select::make('performed_by_user_id')
                    ->label(__('admin.common.fields.performed_by'))
                    ->relationship(name: 'performedByUser', titleAttribute: 'name')
                    ->searchable()
                    ->preload(),
                Select::make('type')
                    ->label(__('admin.common.fields.type'))
                    ->options(BonusTransactionType::class)
                    ->required(),
                TextInput::make('amount')
                    ->label(__('admin.common.fields.amount'))
                    ->numeric()
                    ->required(),
                TextInput::make('balance_delta')
                    ->label(__('admin.common.fields.balance_delta'))
                    ->numeric()
                    ->required(),
                TextInput::make('reserved_delta')
                    ->label(__('admin.common.fields.reserved_delta'))
                    ->numeric()
                    ->default(0),
                Textarea::make('comment')
                    ->label(__('admin.common.fields.comment')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.common.fields.id'))->sortable(),
                TextColumn::make('client.login')->label(__('admin.common.fields.client'))->searchable(),
                TextColumn::make('order.id')->label(__('admin.common.fields.order_number')),
                TextColumn::make('type')->label(__('admin.common.fields.type'))->badge(),
                TextColumn::make('amount')->label(__('admin.common.fields.amount'))->sortable(),
                TextColumn::make('balance_delta')->label(__('admin.common.fields.balance_delta'))->sortable(),
                TextColumn::make('reserved_delta')->label(__('admin.common.fields.reserved_delta'))->sortable(),
                TextColumn::make('performedByUser.name')->label(__('admin.common.fields.performed_by')),
                TextColumn::make('created_at')->label(__('admin.common.fields.created_at'))->dateTime()->sortable(),
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
