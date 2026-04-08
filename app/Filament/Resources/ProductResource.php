<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasTranslationTabs;
use App\Filament\Forms\Components\TranslatableRelationshipSelect;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Category;
use App\Models\Language;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProductResource extends Resource
{
    use HasTranslationTabs;

    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.product.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.product.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.product.plural_model_label');
    }

    /**
     * @return array<int, string>
     */
    public static function getTranslatableAttributes(): array
    {
        return ['name', 'description'];
    }

    public static function getTranslationRelationshipName(): string
    {
        return 'translations';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make(__('admin.resources.product.tabs.label'))
                    ->tabs([
                        Tab::make(__('admin.resources.product.tabs.main'))
                            ->schema([
                                TranslatableRelationshipSelect::make('category_id')
                                    ->label(__('admin.common.fields.category'))
                                    ->translatableRelationship('category')
                                    ->fallbackLabelUsing(
                                        fn (Category $record): string => sprintf(
                                            '%s #%d',
                                            __('admin.resources.category.model_label'),
                                            $record->getKey(),
                                        ),
                                    )
                                    ->required()
                                    ->preload(),
                                TextInput::make('bonus_price')
                                    ->label(__('admin.common.fields.bonus_price'))
                                    ->numeric()
                                    ->required(),
                                TextInput::make('weight_grams')
                                    ->label(__('admin.common.fields.weight'))
                                    ->numeric()
                                    ->required()
                                    ->suffix('g'),
                                TextInput::make('stock_quantity')
                                    ->label(__('admin.common.fields.stock_quantity'))
                                    ->numeric()
                                    ->default(0),
                                TextInput::make('slug')
                                    ->label(__('admin.common.fields.slug'))
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->helperText(__('admin.resources.product.fields.slug_helper'))
                                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? trim($state) : null),
                                FileUpload::make('image')
                                    ->label(__('admin.common.fields.image'))
                                    ->image()
                                    ->directory('products'),
                                Toggle::make('is_active')
                                    ->label(__('admin.common.fields.is_active'))
                                    ->default(true),
                            ])
                            ->columns(2),
                        Tab::make(__('admin.resources.product.tabs.translations'))
                            ->schema([
                                static::makeTranslationTabs(
                                    fn (Language $language, string $statePath): array => [
                                        TextInput::make("{$statePath}.name")
                                            ->label(__('admin.common.fields.name'))
                                            ->required()
                                            ->maxLength(255),
                                        RichEditor::make("{$statePath}.description")
                                            ->label(__('admin.common.fields.description')),
                                    ],
                                ),
                            ]),
                        Tab::make(__('admin.resources.product.tabs.gallery'))
                            ->schema([
                                Repeater::make('images')
                                    ->label(__('admin.common.fields.image'))
                                    ->relationship()
                                    ->defaultItems(0)
                                    ->schema([
                                        FileUpload::make('image')
                                            ->label(__('admin.common.fields.image'))
                                            ->image()
                                            ->directory('products/gallery')
                                            ->required(),
                                        TextInput::make('sort_order')
                                            ->label(__('admin.common.fields.sort_order'))
                                            ->numeric()
                                            ->default(0),
                                    ])
                                    ->columnSpanFull(),
                            ]),
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
                ImageColumn::make('image')->label(__('admin.common.fields.image')),
                TextColumn::make('localized_name')
                    ->label(__('admin.common.fields.name'))
                    ->searchable(
                        query: fn (Builder $query, string $search): Builder => $query->searchLocalizedName($search),
                    ),
                TextColumn::make('slug')->label(__('admin.common.fields.slug'))->searchable(),
                TextColumn::make('category.localized_name')->label(__('admin.common.fields.category')),
                TextColumn::make('bonus_price')->label(__('admin.common.fields.bonus_price'))->sortable(),
                TextColumn::make('weight_grams')->label(__('admin.common.fields.weight'))->suffix(' g')->sortable(),
                TextColumn::make('stock_quantity')->label(__('admin.common.fields.stock_quantity'))->sortable(),
                IconColumn::make('is_active')->label(__('admin.common.fields.is_active'))->boolean(),
                TextColumn::make('created_at')->label(__('admin.common.fields.created_at'))->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('category_id')
                    ->label(__('admin.common.fields.category'))
                    ->options(fn (): array => Category::query()
                        ->withLocalizedName()
                        ->orderBy('localized_name')
                        ->get()
                        ->mapWithKeys(fn (Category $category): array => [
                            $category->getKey() => $category->localized_name
                                ?? sprintf('%s #%d', __('admin.resources.category.model_label'), $category->getKey()),
                        ])
                        ->all()),
            ])
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
        return parent::getEloquentQuery()
            ->withLocalizedName()
            ->with([
                'category' => fn ($query) => $query->withLocalizedName(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
