<?php

namespace App\Filament\Resources;

use App\Actions\Orders\DeleteOrderAction;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\RecipientType;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers\BonusTransactionsRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\StatusHistoriesRelationManager;
use App\Models\Client;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.order.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.order.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.order.plural_model_label');
    }

    private static function recipientTypeMatches(mixed $state, RecipientType $recipientType): bool
    {
        return $state instanceof RecipientType
            ? $state === $recipientType
            : $state === $recipientType->value;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make(__('admin.resources.order.tabs.label'))
                    ->tabs([
                        Tab::make(__('admin.resources.order.tabs.main'))
                            ->schema([
                                Select::make('client_id')
                                    ->label(__('admin.common.fields.client'))
                                    ->relationship(name: 'client', titleAttribute: 'login')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, mixed $state): void {
                                        /** @var Client|null $client */
                                        $client = filled($state) ? Client::query()->find($state) : null;

                                        if (! $client) {
                                            $set('institution_id', null);
                                            $set('recipient_type', RecipientType::Client->value);
                                            $set('recipient_first_name', null);
                                            $set('recipient_last_name', null);
                                            $set('recipient_bin', null);

                                            return;
                                        }

                                        $recipientType = $client->recipient_type ?? RecipientType::Client;

                                        $set('institution_id', $client->institution_id);
                                        $set('recipient_type', $recipientType->value);
                                        $set('recipient_first_name', $recipientType === RecipientType::Other ? $client->recipient_first_name : null);
                                        $set('recipient_last_name', $recipientType === RecipientType::Other ? $client->recipient_last_name : null);
                                        $set('recipient_bin', $recipientType === RecipientType::Other ? $client->recipient_bin : null);
                                    }),
                                Select::make('institution_id')
                                    ->label(__('admin.common.fields.institution'))
                                    ->relationship(name: 'institution', titleAttribute: 'id')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                Select::make('source')
                                    ->label(__('admin.common.fields.source'))
                                    ->options(OrderSource::class)
                                    ->default(OrderSource::Admin)
                                    ->disabled()
                                    ->dehydrated(),
                                Placeholder::make('status_preview')
                                    ->label(__('admin.common.fields.status'))
                                    ->content(fn (?Order $record): string => static::formatStatusPreview($record)),
                                Select::make('created_by_user_id')
                                    ->label(__('admin.common.fields.created_by'))
                                    ->relationship(name: 'createdByUser', titleAttribute: 'name')
                                    ->default(fn (): ?int => auth()->id())
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->searchable()
                                    ->preload(),
                            ])
                            ->columns(2),
                        Tab::make(__('admin.resources.order.tabs.recipient'))
                            ->schema([
                                Select::make('recipient_type')
                                    ->label(__('admin.common.fields.recipient_type'))
                                    ->options(RecipientType::class)
                                    ->default(RecipientType::Client->value)
                                    ->disabled(fn (string $operation): bool => $operation !== 'create')
                                    ->dehydrated(fn (string $operation): bool => $operation === 'create')
                                    ->live()
                                    ->required(),
                                Placeholder::make('recipient_snapshot_hint')
                                    ->label(__('admin.common.fields.recipient_data'))
                                    ->content(fn (Get $get, ?Order $record): string => static::getRecipientPreview($get, $record))
                                    ->hidden(
                                        fn (Get $get, string $operation): bool => $operation !== 'create'
                                            || ! static::recipientTypeMatches($get('recipient_type'), RecipientType::Client)
                                    )
                                    ->columnSpanFull(),
                                TextInput::make('recipient_first_name')
                                    ->label(__('admin.common.fields.recipient_first_name'))
                                    ->requiredIf('recipient_type', RecipientType::Other->value)
                                    ->hidden(
                                        fn (Get $get, string $operation): bool => $operation === 'create'
                                            && ! static::recipientTypeMatches($get('recipient_type'), RecipientType::Other)
                                    )
                                    ->disabled(fn (string $operation): bool => $operation !== 'create')
                                    ->dehydrated(
                                        fn (Get $get, string $operation): bool => $operation === 'create'
                                            && static::recipientTypeMatches($get('recipient_type'), RecipientType::Other)
                                    )
                                    ->maxLength(255),
                                TextInput::make('recipient_last_name')
                                    ->label(__('admin.common.fields.recipient_last_name'))
                                    ->requiredIf('recipient_type', RecipientType::Other->value)
                                    ->hidden(
                                        fn (Get $get, string $operation): bool => $operation === 'create'
                                            && ! static::recipientTypeMatches($get('recipient_type'), RecipientType::Other)
                                    )
                                    ->disabled(fn (string $operation): bool => $operation !== 'create')
                                    ->dehydrated(
                                        fn (Get $get, string $operation): bool => $operation === 'create'
                                            && static::recipientTypeMatches($get('recipient_type'), RecipientType::Other)
                                    )
                                    ->maxLength(255),
                                TextInput::make('recipient_bin')
                                    ->label(__('admin.common.fields.recipient_bin'))
                                    ->requiredIf('recipient_type', RecipientType::Other->value)
                                    ->hidden(
                                        fn (Get $get, string $operation): bool => $operation === 'create'
                                            && ! static::recipientTypeMatches($get('recipient_type'), RecipientType::Other)
                                    )
                                    ->disabled(fn (string $operation): bool => $operation !== 'create')
                                    ->dehydrated(
                                        fn (Get $get, string $operation): bool => $operation === 'create'
                                            && static::recipientTypeMatches($get('recipient_type'), RecipientType::Other)
                                    )
                                    ->maxLength(255),
                            ])
                            ->columns(2),
                        Tab::make(__('admin.resources.order.tabs.parameters'))
                            ->schema([
                                TextInput::make('total_bonus')
                                    ->label(__('admin.common.fields.total_bonus'))
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('total_weight_grams')
                                    ->label(__('admin.common.fields.total_weight'))
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffix('g'),
                                TextInput::make('reserved_bonus_amount')
                                    ->label(__('admin.common.fields.bonus_reserved'))
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(false),
                                Placeholder::make('items_management_hint')
                                    ->label(__('admin.common.fields.products'))
                                    ->content(__('admin.resources.order.fields.items_hint'))
                                    ->hidden(fn (string $operation): bool => $operation !== 'create')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                        Tab::make(__('admin.resources.order.tabs.status_dates'))
                            ->schema([
                                Placeholder::make('placed_at_preview')
                                    ->label(__('admin.common.fields.placed_at'))
                                    ->content(
                                        fn (?Order $record): string => static::formatDateTimePreview(
                                            $record,
                                            'placed_at',
                                            __('admin.resources.order.messages.placed_at_pending'),
                                        )
                                    ),
                                Placeholder::make('status_changed_at_preview')
                                    ->label(__('admin.common.fields.status_changed_at'))
                                    ->content(
                                        fn (?Order $record): string => static::formatDateTimePreview(
                                            $record,
                                            'status_changed_at',
                                            __('admin.resources.order.messages.status_not_changed'),
                                        )
                                    ),
                                Placeholder::make('delivered_at_preview')
                                    ->label(__('admin.common.fields.delivered_at'))
                                    ->content(
                                        fn (?Order $record): string => static::formatDateTimePreview(
                                            $record,
                                            'delivered_at',
                                            __('admin.resources.order.messages.not_delivered'),
                                        )
                                    ),
                                Placeholder::make('cancelled_at_preview')
                                    ->label(__('admin.common.fields.cancelled_at'))
                                    ->content(
                                        fn (?Order $record): string => static::formatDateTimePreview(
                                            $record,
                                            'cancelled_at',
                                            __('admin.resources.order.messages.not_cancelled'),
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
            return __('admin.resources.order.messages.recipient_from_client');
        }

        /** @var Client|null $client */
        $client = Client::query()->find($clientId);

        if (! $client) {
            return __('admin.resources.order.messages.client_not_found');
        }

        return static::formatRecipientPreview($client->recipient_full_name, $client->recipient_bin_value);
    }

    private static function formatRecipientPreview(string $fullName, ?string $bin): string
    {
        if (! filled($fullName) && ! filled($bin)) {
            return __('admin.resources.order.messages.recipient_empty');
        }

        if (filled($fullName) && filled($bin)) {
            return sprintf('%s, %s', $fullName, $bin);
        }

        return filled($fullName) ? $fullName : (string) $bin;
    }

    private static function formatStatusPreview(?Order $record): string
    {
        $status = $record?->status ?? OrderStatus::New;

        return (string) ($status->getLabel() ?? str($status->value)->headline()->toString());
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
                TextColumn::make('id')->label(__('admin.common.fields.id'))->sortable(),
                TextColumn::make('client.login')->label(__('admin.common.fields.client'))->searchable(),
                TextColumn::make('recipient_full_name')
                    ->label(__('admin.common.fields.recipient')),
                TextColumn::make('institution.translations.name')->label(__('admin.common.fields.institution')),
                TextColumn::make('source')->label(__('admin.common.fields.source'))->badge(),
                TextColumn::make('status')->label(__('admin.common.fields.status'))->badge(),
                TextColumn::make('total_bonus')->label(__('admin.common.fields.total_bonus'))->sortable(),
                TextColumn::make('total_weight_grams')->label(__('admin.common.fields.total_weight'))->suffix(' g')->sortable(),
                TextColumn::make('placed_at')->label(__('admin.common.fields.placed_at'))->dateTime()->sortable(),
                TextColumn::make('created_at')->label(__('admin.common.fields.created_at'))->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(OrderStatus::class),
                SelectFilter::make('source')
                    ->options(OrderSource::class),
            ])
            ->recordActions([
                EditAction::make(),
                static::makeDeleteAction(),
            ])
            ->recordUrl(fn (Order $record): string => static::getUrl('edit', ['record' => $record]));
    }

    public static function makeDeleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->label(__('admin.common.actions.delete'))
            ->visible(fn (Order $record): bool => $record->canBeDeleted())
            ->modalDescription(__('admin.resources.order.messages.delete_description'))
            ->successNotificationTitle(__('admin.resources.order.messages.deleted'))
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
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
