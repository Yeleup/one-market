<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Actions\Orders\SyncOrderTotalsAction;
use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\Product;
use Closure;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relation_managers.items');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label(__('admin.common.fields.product'))
                    ->relationship(
                        name: 'product',
                        titleAttribute: 'id',
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->withLocalizedName()
                            ->with('images'),
                    )
                    ->getOptionLabelFromRecordUsing(
                        fn (Product $record): string => sprintf(
                            '#%d %s',
                            $record->getKey(),
                            $record->localized_name ?? __('admin.common.messages.untitled'),
                        ),
                    )
                    ->searchable(['id'])
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                        $product = filled($state) ? $this->findProduct((int) $state) : null;

                        if (! $product) {
                            return;
                        }

                        $set('product_name', $product->localized_name ?? sprintf('%s #%d', __('admin.resources.product.model_label'), $product->getKey()));
                        $set('product_image', $product->image ?: $product->images->sortBy('sort_order')->first()?->image);
                        $set('price_bonus', $product->bonus_price);
                        $set('weight_grams', $product->weight_grams);

                        $this->updateLineTotals($get, $set);
                    }),
                TextInput::make('product_name')
                    ->label(__('admin.common.fields.product_name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('price_bonus')
                    ->label(__('admin.common.fields.price_bonus'))
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => $this->updateLineTotals($get, $set)),
                TextInput::make('weight_grams')
                    ->label(__('admin.common.fields.weight'))
                    ->numeric()
                    ->required()
                    ->default(0)
                    ->suffix('g')
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => $this->updateLineTotals($get, $set)),
                TextInput::make('quantity')
                    ->label(__('admin.common.fields.quantity'))
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->minValue(1)
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set) => $this->updateLineTotals($get, $set)),
                TextInput::make('line_total_bonus')
                    ->label(__('admin.common.fields.line_total_bonus'))
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('line_total_weight_grams')
                    ->label(__('admin.common.fields.line_total_weight'))
                    ->numeric()
                    ->disabled()
                    ->dehydrated(false)
                    ->suffix('g'),
            ])
            ->columns();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                ImageColumn::make('product_image')->label(__('admin.common.fields.product_image')),
                TextColumn::make('product_name')->label(__('admin.common.fields.product_name'))->searchable(),
                TextColumn::make('quantity')->label(__('admin.common.fields.quantity'))->sortable(),
                TextColumn::make('price_bonus')->label(__('admin.common.fields.price_bonus'))->sortable(),
                TextColumn::make('line_total_bonus')->label(__('admin.common.fields.line_total_bonus'))->sortable(),
                TextColumn::make('weight_grams')->label(__('admin.common.fields.weight'))->suffix(' g')->sortable(),
                TextColumn::make('line_total_weight_grams')->label(__('admin.common.fields.line_total_weight'))->suffix(' g')->sortable(),
            ])
            ->headerActions([
                $this->makeCreateAction(),
            ])
            ->recordActions([
                $this->makeEditAction(),
                $this->makeDeleteAction(),
            ])
            ->defaultSort('id');
    }

    private function makeCreateAction(): CreateAction
    {
        return CreateAction::make()
            ->hidden(fn (): bool => ! $this->canManageItems())
            ->mutateDataUsing(fn (array $data): array => $this->normalizeItemData($data))
            ->using(function (CreateAction $action, array $data): OrderItem {
                /** @var OrderItem $item */
                $item = $this->runItemMutation($action, function () use ($data): OrderItem {
                    $item = new OrderItem($data);

                    return DB::transaction(function () use ($item): OrderItem {
                        $this->getOwnerRecord()->items()->save($item);

                        app(SyncOrderTotalsAction::class)->handle($this->getOwnerRecord(), auth()->id());

                        return $item;
                    });
                });

                return $item;
            });
    }

    private function makeEditAction(): EditAction
    {
        return EditAction::make()
            ->hidden(fn (): bool => ! $this->canManageItems())
            ->mutateDataUsing(fn (array $data): array => $this->normalizeItemData($data))
            ->using(function (EditAction $action, OrderItem $record, array $data): OrderItem {
                /** @var OrderItem $item */
                $item = $this->runItemMutation($action, function () use ($record, $data): OrderItem {
                    return DB::transaction(function () use ($record, $data): OrderItem {
                        $record->update($data);

                        app(SyncOrderTotalsAction::class)->handle($this->getOwnerRecord(), auth()->id());

                        return $record->fresh();
                    });
                });

                return $item;
            });
    }

    private function makeDeleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->hidden(fn (): bool => ! $this->canManageItems())
            ->using(function (DeleteAction $action, OrderItem $record): bool {
                return $this->runItemMutation($action, function () use ($record): bool {
                    return DB::transaction(function () use ($record): bool {
                        $result = (bool) $record->delete();

                        if ($result) {
                            app(SyncOrderTotalsAction::class)->handle($this->getOwnerRecord(), auth()->id());
                        }

                        return $result;
                    });
                });
            });
    }

    /**
     * @template TMutationResult
     *
     * @param  Closure(): TMutationResult  $callback
     * @return TMutationResult
     */
    private function runItemMutation(CreateAction|EditAction|DeleteAction $action, Closure $callback): mixed
    {
        try {
            return $callback();
        } catch (RuntimeException $exception) {
            Notification::make()
                ->danger()
                ->title(__('admin.actions.order.notifications.failed'))
                ->body($exception->getMessage())
                ->send();

            $action->halt();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeItemData(array $data): array
    {
        $product = filled($data['product_id'] ?? null) ? $this->findProduct((int) $data['product_id']) : null;
        $quantity = max(1, (int) ($data['quantity'] ?? 1));
        $priceBonus = (int) ($data['price_bonus'] ?? $product?->bonus_price ?? 0);
        $weightGrams = (int) ($data['weight_grams'] ?? $product?->weight_grams ?? 0);

        return [
            ...$data,
            'quantity' => $quantity,
            'price_bonus' => $priceBonus,
            'weight_grams' => $weightGrams,
            'product_name' => filled($data['product_name'] ?? null)
                ? $data['product_name']
                : ($product?->localized_name ?? sprintf('%s #%d', __('admin.resources.product.model_label'), $data['product_id'])),
            'product_image' => filled($data['product_image'] ?? null)
                ? $data['product_image']
                : $this->getProductDefaultImage($product),
            'line_total_bonus' => $priceBonus * $quantity,
            'line_total_weight_grams' => $weightGrams * $quantity,
        ];
    }

    private function canManageItems(): bool
    {
        return ! in_array($this->getOwnerRecord()->status, [OrderStatus::Delivered, OrderStatus::Cancelled], true);
    }

    private function updateLineTotals(Get $get, Set $set): void
    {
        $priceBonus = (int) ($get('price_bonus') ?? 0);
        $weightGrams = (int) ($get('weight_grams') ?? 0);
        $quantity = max(1, (int) ($get('quantity') ?? 1));

        $set('line_total_bonus', $priceBonus * $quantity);
        $set('line_total_weight_grams', $weightGrams * $quantity);
    }

    private function findProduct(int $productId): ?Product
    {
        return Product::query()
            ->withLocalizedName()
            ->with('images')
            ->find($productId);
    }

    private function getProductDefaultImage(?Product $product): ?string
    {
        if (! $product) {
            return null;
        }

        return $product->image ?: $product->images->sortBy('sort_order')->first()?->image;
    }
}
