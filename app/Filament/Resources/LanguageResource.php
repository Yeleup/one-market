<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LanguageResource\Pages;
use App\Models\Language;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;
use UnitEnum;

class LanguageResource extends Resource
{
    protected static ?string $model = Language::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-language';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 7;

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.language.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.resources.language.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.language.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('code')
                    ->label(__('admin.common.fields.code'))
                    ->options(static::getAvailableLanguageOptions())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label(__('admin.common.fields.name'))
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_default')
                    ->label(__('admin.common.fields.is_default'))
                    ->default(false)
                    ->helperText(__('admin.resources.language.fields.default_helper')),
                Toggle::make('is_active')
                    ->label(__('admin.common.fields.is_active'))
                    ->default(true),
                TextInput::make('sort_order')
                    ->label(__('admin.common.fields.sort_order'))
                    ->numeric()
                    ->default(0),
            ]);
    }

    /**
     * @return array<string, string>
     */
    protected static function getAvailableLanguageOptions(): array
    {
        return collect(File::directories(lang_path()))
            ->mapWithKeys(fn (string $directory): array => [basename($directory) => basename($directory)])
            ->sortKeys()
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.common.fields.id'))->sortable(),
                TextColumn::make('code')->label(__('admin.common.fields.code'))->searchable(),
                TextColumn::make('name')->label(__('admin.common.fields.name'))->searchable(),
                IconColumn::make('is_default')->label(__('admin.common.fields.is_default'))->boolean(),
                IconColumn::make('is_active')->label(__('admin.common.fields.is_active'))->boolean(),
                TextColumn::make('sort_order')->label(__('admin.common.fields.sort_order'))->sortable(),
            ])
            ->defaultSort('sort_order')
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
            'index' => Pages\ListLanguages::route('/'),
            'create' => Pages\CreateLanguage::route('/create'),
            'edit' => Pages\EditLanguage::route('/{record}/edit'),
        ];
    }
}
