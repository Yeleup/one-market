<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasTranslationTabs;
use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use App\Models\Language;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CategoryResource extends Resource
{
    use HasTranslationTabs;

    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.category.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.category.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.category.plural_model_label');
    }

    /**
     * @return array<int, string>
     */
    public static function getTranslatableAttributes(): array
    {
        return ['name'];
    }

    public static function getTranslationRelationshipName(): string
    {
        return 'translations';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->label(__('admin.common.fields.slug'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->helperText(__('admin.resources.category.fields.slug_helper')),
                Toggle::make('is_active')
                    ->label(__('admin.common.fields.is_active'))
                    ->default(true),
                static::makeTranslationTabs(
                    fn (Language $language, string $statePath): array => [
                        TextInput::make("{$statePath}.name")
                            ->label(__('admin.common.fields.name'))
                            ->required()
                            ->maxLength(255),
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
                TextColumn::make('slug')->label(__('admin.common.fields.slug'))->searchable(),
                IconColumn::make('is_active')->label(__('admin.common.fields.is_active'))->boolean(),
                TextColumn::make('products_count')->counts('products')->label(__('admin.resources.category.fields.products_count')),
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
