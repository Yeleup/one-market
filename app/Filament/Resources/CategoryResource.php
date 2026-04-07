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
use UnitEnum;

class CategoryResource extends Resource
{
    use HasTranslationTabs;

    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 3;

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
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->helperText('Общий slug для всех языков.'),
                Toggle::make('is_active')
                    ->default(true),
                static::makeTranslationTabs(
                    fn (Language $language, string $statePath): array => [
                        TextInput::make("{$statePath}.name")
                            ->label('Name')
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
                TextColumn::make('id')->sortable(),
                TextColumn::make('translations.name')->label('Name')->searchable(),
                TextColumn::make('slug')->searchable(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('products_count')->counts('products')->label('Products'),
                TextColumn::make('created_at')->dateTime()->sortable(),
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
