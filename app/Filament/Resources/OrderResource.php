<?php

namespace App\Filament\Resources;

use App\Actions\Orders\DeleteOrderAction;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\BonusTransactionsRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\StatusHistoriesRelationManager;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_id')
                    ->relationship(name: 'client', titleAttribute: 'login')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('institution_id')
                    ->relationship(name: 'institution', titleAttribute: 'id')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('source')
                    ->options(OrderSource::class)
                    ->default(OrderSource::Admin)
                    ->disabled()
                    ->dehydrated(),
                Select::make('status')
                    ->options(OrderStatus::class)
                    ->default(OrderStatus::New)
                    ->disabled(fn (string $operation): bool => $operation !== 'create')
                    ->dehydrated(fn (string $operation): bool => $operation === 'create')
                    ->required(),
                Placeholder::make('items_management_hint')
                    ->label('Товары')
                    ->content('Позиции заказа добавляются после сохранения заказа в блоке "Items" на странице редактирования.')
                    ->hidden(fn (string $operation): bool => $operation !== 'create')
                    ->columnSpanFull(),
                TextInput::make('total_bonus')
                    ->label('Total bonus')
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('total_weight_grams')
                    ->label('Total weight')
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false)
                    ->suffix('g'),
                TextInput::make('reserved_bonus_amount')
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false),
                DateTimePicker::make('placed_at')
                    ->default(now()),
                DateTimePicker::make('status_changed_at'),
                DateTimePicker::make('delivered_at'),
                DateTimePicker::make('cancelled_at'),
                Select::make('created_by_user_id')
                    ->relationship(name: 'createdByUser', titleAttribute: 'name')
                    ->default(fn (): ?int => auth()->id())
                    ->disabled()
                    ->dehydrated(false)
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('client.login')->searchable(),
                TextColumn::make('institution.translations.name')->label('Institution'),
                TextColumn::make('source')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('total_bonus')->sortable(),
                TextColumn::make('total_weight_grams')->suffix(' g')->sortable(),
                TextColumn::make('placed_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(OrderStatus::class),
                SelectFilter::make('source')
                    ->options(OrderSource::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                static::makeDeleteAction(),
            ]);
    }

    public static function makeDeleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->label('Удалить')
            ->visible(fn (Order $record): bool => $record->canBeDeleted())
            ->modalDescription('Будут удалены сам заказ, его позиции, история статусов и связанные бонусные транзакции.')
            ->successNotificationTitle('Заказ удалён.')
            ->using(function (Order $record): bool {
                app(DeleteOrderAction::class)->handle($record);

                return true;
            });
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            BonusTransactionsRelationManager::class,
            StatusHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
