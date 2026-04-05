<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasTranslationTabs;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Language;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ProductResource extends Resource
{
    use HasTranslationTabs;

    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 4;

    /**
     * @return array<int, string>
     */
    public static function getTranslatableAttributes(): array
    {
        return ['name', 'description', 'slug'];
    }

    public static function getTranslationRelationshipName(): string
    {
        return 'translations';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->relationship(name: 'category', titleAttribute: 'id')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('bonus_price')
                    ->numeric()
                    ->required(),
                TextInput::make('weight_grams')
                    ->numeric()
                    ->required()
                    ->suffix('g'),
                TextInput::make('stock_quantity')
                    ->numeric()
                    ->default(0),
                FileUpload::make('image')
                    ->image()
                    ->directory('products'),
                Toggle::make('is_active')
                    ->default(true),
                static::makeTranslationTabs(
                    fn (Language $language, string $statePath): array => [
                        TextInput::make("{$statePath}.name")
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        Textarea::make("{$statePath}.description")
                            ->label('Description'),
                        TextInput::make("{$statePath}.slug")
                            ->label('Slug')
                            ->maxLength(255),
                    ],
                ),
                Repeater::make('images')
                    ->relationship()
                    ->schema([
                        FileUpload::make('image')
                            ->image()
                            ->directory('products/gallery')
                            ->required(),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                ImageColumn::make('image'),
                TextColumn::make('translations.name')->label('Name')->searchable(),
                TextColumn::make('category.translations.name')->label('Category'),
                TextColumn::make('bonus_price')->sortable(),
                TextColumn::make('weight_grams')->suffix(' g')->sortable(),
                TextColumn::make('stock_quantity')->sortable(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'id'),
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
