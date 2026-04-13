<?php

use App\Filament\Resources\ProductResource;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component as LivewireComponent;

it('uses a select tree for the product category field', function (): void {
    $schema = ProductResource::form(Schema::make(makeProductResourceSchemaHost()));

    $categoryField = collect(getSchemaComponents($schema->getComponents()))
        ->first(
            fn (Component $component): bool => $component instanceof SelectTree
                && $component->getName() === 'category_id',
        );

    expect($categoryField)->toBeInstanceOf(SelectTree::class);
});

function makeProductResourceSchemaHost(): HasSchemas
{
    return new class extends LivewireComponent implements HasSchemas
    {
        use InteractsWithSchemas;

        public function render(): string
        {
            return '';
        }

        public function getDefaultTestingSchemaName(): ?string
        {
            return null;
        }
    };
}

/**
 * @param  array<Component>  $components
 * @return array<Component>
 */
function getSchemaComponents(array $components): array
{
    $resolvedComponents = [];

    foreach ($components as $component) {
        $resolvedComponents[] = $component;

        $childComponents = $component->getChildComponents();

        if ($childComponents !== []) {
            array_push($resolvedComponents, ...getSchemaComponents($childComponents));
        }
    }

    return $resolvedComponents;
}
