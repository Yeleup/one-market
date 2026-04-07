<?php

namespace App\Filament\Resources;

use App\Actions\Orders\DeleteOrderAction;
use App\Enums\OrderRecipientType;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\BonusTransactionsRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\StatusHistoriesRelationManager;
use App\Models\Client;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
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
                Tabs::make('Основная информация заказа')
                    ->tabs([
                        Tab::make('Основное')
                            ->schema([
                                Select::make('client_id')
                                    ->relationship(name: 'client', titleAttribute: 'login')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live(),
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
                                Placeholder::make('status_preview')
                                    ->label('Status')
                                    ->content(fn (?Order $record): string => static::formatStatusPreview($record)),
                                Select::make('created_by_user_id')
                                    ->relationship(name: 'createdByUser', titleAttribute: 'name')
                                    ->default(fn (): ?int => auth()->id())
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->searchable()
                                    ->preload(),
                            ])
                            ->columns(2),
                        Tab::make('Получатель')
                            ->schema([
                                Select::make('recipient_type')
                                    ->label('Получатель')
                                    ->options([
                                        OrderRecipientType::Client->value => 'Сам клиент',
                                        OrderRecipientType::Other->value => 'Другой получатель',
                                    ])
                                    ->default(OrderRecipientType::Client->value)
                                    ->disabled(fn (string $operation): bool => $operation !== 'create')
                                    ->dehydrated(fn (string $operation): bool => $operation === 'create')
                                    ->live()
                                    ->required(),
                                Placeholder::make('recipient_snapshot_hint')
                                    ->label('Данные получателя')
                                    ->content(fn (Get $get, ?Order $record): string => static::getRecipientPreview($get, $record))
                                    ->hidden(
                                        fn (Get $get, string $operation): bool => $operation !== 'create'
                                            || $get('recipient_type') !== OrderRecipientType::Client->value
                                    )
                                    ->columnSpanFull(),
                                TextInput::make('recipient_first_name')
                                    ->label('Имя получателя')
                                    ->requiredIf('recipient_type', OrderRecipientType::Other->value)
                                    ->hidden(
                                        fn (Get $get, string $operation): bool => $operation === 'create'
                                            && $get('recipient_type') !== OrderRecipientType::Other->value
                                    )
                                    ->disabled(fn (string $operation): bool => $operation !== 'create')
                                    ->dehydrated(
                                        fn (Get $get, string $operation): bool => $operation === 'create'
                                            && $get('recipient_type') === OrderRecipientType::Other->value
                                    )
                                    ->maxLength(255),
                                TextInput::make('recipient_last_name')
                                    ->label('Фамилия получателя')
                                    ->requiredIf('recipient_type', OrderRecipientType::Other->value)
                                    ->hidden(
                                        fn (Get $get, string $operation): bool => $operation === 'create'
                                            && $get('recipient_type') !== OrderRecipientType::Other->value
                                    )
                                    ->disabled(fn (string $operation): bool => $operation !== 'create')
                                    ->dehydrated(
                                        fn (Get $get, string $operation): bool => $operation === 'create'
                                            && $get('recipient_type') === OrderRecipientType::Other->value
                                    )
                                    ->maxLength(255),
                                TextInput::make('recipient_bin')
                                    ->label('ИИН / БИН получателя')
                                    ->requiredIf('recipient_type', OrderRecipientType::Other->value)
                                    ->hidden(
                                        fn (Get $get, string $operation): bool => $operation === 'create'
                                            && $get('recipient_type') !== OrderRecipientType::Other->value
                                    )
                                    ->disabled(fn (string $operation): bool => $operation !== 'create')
                                    ->dehydrated(
                                        fn (Get $get, string $operation): bool => $operation === 'create'
                                            && $get('recipient_type') === OrderRecipientType::Other->value
                                    )
                                    ->maxLength(255),
                            ])
                            ->columns(2),
                        Tab::make('Параметры')
                            ->schema([
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
                                Placeholder::make('items_management_hint')
                                    ->label('Товары')
                                    ->content('Позиции заказа добавляются после сохранения заказа в блоке "Items" на странице редактирования.')
                                    ->hidden(fn (string $operation): bool => $operation !== 'create')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                        Tab::make('Статусы и даты')
                            ->schema([
                                Placeholder::make('placed_at_preview')
                                    ->label('Placed at')
                                    ->content(
                                        fn (?Order $record): string => static::formatDateTimePreview(
                                            $record,
                                            'placed_at',
                                            'Будет установлено автоматически при создании.',
                                        )
                                    ),
                                Placeholder::make('status_changed_at_preview')
                                    ->label('Status changed at')
                                    ->content(
                                        fn (?Order $record): string => static::formatDateTimePreview(
                                            $record,
                                            'status_changed_at',
                                            'Ещё не менялся.',
                                        )
                                    ),
                                Placeholder::make('delivered_at_preview')
                                    ->label('Delivered at')
                                    ->content(
                                        fn (?Order $record): string => static::formatDateTimePreview(
                                            $record,
                                            'delivered_at',
                                            'Ещё не доставлен.',
                                        )
                                    ),
                                Placeholder::make('cancelled_at_preview')
                                    ->label('Cancelled at')
                                    ->content(
                                        fn (?Order $record): string => static::formatDateTimePreview(
                                            $record,
                                            'cancelled_at',
                                            'Не отменён.',
                                        )
                                    ),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    private static function getRecipientPreview(Get $get, ?Order $record): string
    {
        if ($record) {
            return static::formatRecipientPreview($record->recipient_full_name, $record->recipient_bin_value);
        }

        $clientId = $get('client_id');

        if (! filled($clientId)) {
            return 'Выберите клиента, и данные получателя заполнятся из его карточки.';
        }

        /** @var Client|null $client */
        $client = Client::query()->find($clientId);

        if (! $client) {
            return 'Клиент не найден.';
        }

        return static::formatRecipientPreview($client->full_name, $client->bin);
    }

    private static function formatRecipientPreview(string $fullName, ?string $bin): string
    {
        if (! filled($fullName) && ! filled($bin)) {
            return 'Данные получателя не заполнены.';
        }

        if (filled($fullName) && filled($bin)) {
            return sprintf('%s, %s', $fullName, $bin);
        }

        return filled($fullName) ? $fullName : (string) $bin;
    }

    private static function formatStatusPreview(?Order $record): string
    {
        $status = $record?->status ?? OrderStatus::New;

        return str($status->value)->headline()->toString();
    }

    private static function formatDateTimePreview(?Order $record, string $attribute, string $fallback): string
    {
        $dateTime = $record?->{$attribute};

        if ($dateTime === null) {
            return $fallback;
        }

        return $dateTime->toDateTimeString();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('client.login')->searchable(),
                TextColumn::make('recipient_full_name')
                    ->label('Recipient'),
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
