<?php

namespace App\Filament\Resources;

use App\Enums\RecipientType;
use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers\BonusTransactionsRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\OrdersRelationManager;
use App\Models\Client;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
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

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeRecipientData(array $data): array
    {
        $rawRecipientType = $data['recipient_type'] ?? RecipientType::Client;
        $recipientType = $rawRecipientType instanceof RecipientType
            ? $rawRecipientType
            : RecipientType::tryFrom((string) $rawRecipientType);

        if (! $recipientType) {
            throw ValidationException::withMessages([
                'data.recipient_type' => 'Некорректный тип получателя.',
            ]);
        }

        $data['recipient_type'] = $recipientType;

        if ($recipientType === RecipientType::Client) {
            $data['recipient_first_name'] = null;
            $data['recipient_last_name'] = null;
            $data['recipient_bin'] = null;

            return $data;
        }

        $data['recipient_first_name'] = static::normalizeRecipientValue($data, 'recipient_first_name', 'Имя получателя обязательно.');
        $data['recipient_last_name'] = static::normalizeRecipientValue($data, 'recipient_last_name', 'Фамилия получателя обязательна.');
        $data['recipient_bin'] = static::normalizeRecipientValue($data, 'recipient_bin', 'ИИН/БИН получателя обязателен.');

        return $data;
    }

    private static function recipientTypeMatches(mixed $state, RecipientType $recipientType): bool
    {
        return $state instanceof RecipientType
            ? $state === $recipientType
            : $state === $recipientType->value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function normalizeRecipientValue(array $data, string $key, string $message): string
    {
        $value = trim((string) ($data[$key] ?? ''));

        if (! filled($value)) {
            throw ValidationException::withMessages([
                "data.{$key}" => $message,
            ]);
        }

        return $value;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make(__('admin.resources.client.tabs.label'))
                    ->tabs([
                        Tab::make(__('admin.resources.client.tabs.main'))
                            ->schema([
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
                                Select::make('institution_id')
                                    ->label(__('admin.common.fields.institution'))
                                    ->relationship(name: 'institution', titleAttribute: 'id')
                                    ->searchable()
                                    ->preload(),
                                Toggle::make('is_active')
                                    ->label(__('admin.common.fields.is_active'))
                                    ->default(true),
                            ])
                            ->columns(2),
                        Tab::make(__('admin.resources.client.tabs.recipient'))
                            ->schema([
                                Select::make('recipient_type')
                                    ->label(__('admin.common.fields.recipient_type'))
                                    ->options(RecipientType::class)
                                    ->default(RecipientType::Client->value)
                                    ->live()
                                    ->required(),
                                TextInput::make('recipient_first_name')
                                    ->label(__('admin.common.fields.recipient_first_name'))
                                    ->requiredIf('recipient_type', RecipientType::Other->value)
                                    ->hidden(fn (Get $get): bool => ! static::recipientTypeMatches($get('recipient_type'), RecipientType::Other))
                                    ->maxLength(255),
                                TextInput::make('recipient_last_name')
                                    ->label(__('admin.common.fields.recipient_last_name'))
                                    ->requiredIf('recipient_type', RecipientType::Other->value)
                                    ->hidden(fn (Get $get): bool => ! static::recipientTypeMatches($get('recipient_type'), RecipientType::Other))
                                    ->maxLength(255),
                                TextInput::make('recipient_bin')
                                    ->label(__('admin.common.fields.recipient_bin'))
                                    ->requiredIf('recipient_type', RecipientType::Other->value)
                                    ->hidden(fn (Get $get): bool => ! static::recipientTypeMatches($get('recipient_type'), RecipientType::Other))
                                    ->maxLength(255),
                            ])
                            ->columns(2),
                        Tab::make(__('admin.resources.client.tabs.bonuses'))
                            ->schema([
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
                                    ->content(fn (Get $get): int => max(0, ((int) ($get('bonus_balance') ?? 0)) - ((int) ($get('bonus_reserved') ?? 0)))),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(2);
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
                TextColumn::make('institution.id')->label(__('admin.common.fields.institution')),
                TextColumn::make('recipient_type')->label(__('admin.common.fields.recipient_type'))->badge(),
                TextColumn::make('recipient_full_name')->label(__('admin.common.fields.recipient')),
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
                EditAction::make(),
            ])
            ->recordUrl(fn (Client $record): string => static::getUrl('edit', ['record' => $record]))
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
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
