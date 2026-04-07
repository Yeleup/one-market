<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers\BonusTransactionsRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\OrdersRelationManager;
use App\Models\Client;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.client.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.client.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.client.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->label(__('admin.common.fields.first_name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('last_name')
                    ->label(__('admin.common.fields.last_name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('bin')
                    ->label(__('admin.common.fields.bin'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('login')
                    ->label(__('admin.common.fields.login'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('password')
                    ->label(__('admin.common.fields.password'))
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->maxLength(255),
                TextInput::make('bonus_balance')
                    ->label(__('admin.common.fields.bonus_balance'))
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('bonus_reserved')
                    ->label(__('admin.common.fields.bonus_reserved'))
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),
                Placeholder::make('available_bonus')
                    ->label(__('admin.common.fields.available_bonus'))
                    ->content(fn (?Client $record): int => max(0, ($record?->bonus_balance ?? 0) - ($record?->bonus_reserved ?? 0))),
                Toggle::make('is_active')
                    ->label(__('admin.common.fields.is_active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.common.fields.id'))->sortable(),
                TextColumn::make('full_name')
                    ->label(__('admin.common.fields.client'))
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('bin')->label(__('admin.common.fields.bin'))->searchable(),
                TextColumn::make('login')->label(__('admin.common.fields.login'))->searchable(),
                TextColumn::make('bonus_balance')->label(__('admin.common.fields.bonus_balance'))->sortable(),
                TextColumn::make('bonus_reserved')->label(__('admin.common.fields.bonus_reserved'))->sortable(),
                TextColumn::make('available_bonus')
                    ->label(__('admin.common.fields.available_bonus'))
                    ->state(fn (Client $record): int => max(0, $record->bonus_balance - $record->bonus_reserved))
                    ->sortable(false),
                IconColumn::make('is_active')->label(__('admin.common.fields.is_active'))->boolean(),
                TextColumn::make('created_at')->label(__('admin.common.fields.created_at'))->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class,
            BonusTransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'view' => Pages\ViewClient::route('/{record}'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
