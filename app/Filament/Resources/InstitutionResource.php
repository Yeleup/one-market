<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasTranslationTabs;
use App\Filament\Resources\InstitutionResource\Pages;
use App\Models\Institution;
use App\Models\Language;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class InstitutionResource extends Resource
{
    use HasTranslationTabs;

    protected static ?string $model = Institution::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.institution.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.institution.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.institution.plural_model_label');
    }

    /**
     * @return array<int, string>
     */
    public static function getTranslatableAttributes(): array
    {
        return ['name', 'address', 'description'];
    }

    public static function getTranslationRelationshipName(): string
    {
        return 'translations';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('max_weight_grams')
                    ->label(__('admin.common.fields.max_weight'))
                    ->numeric()
                    ->required()
                    ->suffix('g'),
                Toggle::make('is_active')
                    ->label(__('admin.common.fields.is_active'))
                    ->default(true),
                static::makeTranslationTabs(
                    fn (Language $language, string $statePath): array => [
                        TextInput::make("{$statePath}.name")
                            ->label(__('admin.common.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make("{$statePath}.address")
                            ->label(__('admin.common.fields.address'))
                            ->required()
                            ->maxLength(255),
                        RichEditor::make("{$statePath}.description")
                            ->label(__('admin.common.fields.description')),
                    ],
                ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.common.fields.id'))->sortable(),
                TextColumn::make('localized_name')
                    ->label(__('admin.common.fields.name'))
                    ->searchable(
                        query: fn (Builder $query, string $search): Builder => $query->searchLocalizedName($search),
                    ),
                TextColumn::make('max_weight_grams')->label(__('admin.common.fields.max_weight'))->suffix(' g')->sortable(),
                IconColumn::make('is_active')->label(__('admin.common.fields.is_active'))->boolean(),
                TextColumn::make('created_at')->label(__('admin.common.fields.created_at'))->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withLocalizedName();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstitutions::route('/'),
            'create' => Pages\CreateInstitution::route('/create'),
            'edit' => Pages\EditInstitution::route('/{record}/edit'),
        ];
    }
}
