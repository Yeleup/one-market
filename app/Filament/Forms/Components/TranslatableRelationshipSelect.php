<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Select;
use Filament\Support\Services\RelationshipJoiner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class TranslatableRelationshipSelect extends Select
{
    protected ?Closure $fallbackLabelUsing = null;

    public function translatableRelationship(
        string|Closure|null $name = null,
        string|Closure|null $titleAttribute = 'id',
        ?Closure $modifyQueryUsing = null,
        bool $ignoreRecord = false,
        string $translationRelationship = 'translations',
    ): static {
        $this->relationship(
            name: $name,
            titleAttribute: $titleAttribute,
            modifyQueryUsing: function (Builder $query, ?string $search = null) use ($modifyQueryUsing): Builder {
                return $this->prepareLocalizedQuery($query, $modifyQueryUsing, $search);
            },
            ignoreRecord: $ignoreRecord,
        );

        $this->getOptionLabelFromRecordUsing(
            fn (Model $record): string => $this->getLocalizedOptionLabel($record),
        );

        $this->getSearchResultsUsing(function (TranslatableRelationshipSelect $component, ?string $search) use ($ignoreRecord, $modifyQueryUsing): array {
            $relationship = Relation::noConstraints(fn () => $component->getRelationship());
            $relationshipQuery = app(RelationshipJoiner::class)->prepareQueryForNoConstraints($relationship);

            if ($ignoreRecord && ($record = $component->getRecord())) {
                $relationshipQuery->where($record->getQualifiedKeyName(), '!=', $record->getKey());
            }

            $relationshipQuery = $component->prepareLocalizedQuery($relationshipQuery, $modifyQueryUsing, $search);

            if (filled($search)) {
                $component->applyLocalizedSearchConstraint($relationshipQuery, $search, $relationship);
            }

            $baseRelationshipQuery = $relationshipQuery->getQuery();

            if (isset($baseRelationshipQuery->limit)) {
                $component->optionsLimit($baseRelationshipQuery->limit);
            } else {
                $relationshipQuery->limit($component->getOptionsLimit());
            }

            $qualifiedRelatedKeyName = $component->getQualifiedRelatedKeyNameForRelationship($relationship);
            $relatedKeyName = str($qualifiedRelatedKeyName)->afterLast('.')->toString();

            return $relationshipQuery
                ->get()
                ->mapWithKeys(fn (Model $record): array => [
                    $record->getAttribute($relatedKeyName) => $component->getLocalizedOptionLabel($record),
                ])
                ->toArray();
        });

        $this->searchable();

        return $this;
    }

    public function fallbackLabelUsing(?Closure $callback): static
    {
        $this->fallbackLabelUsing = $callback;

        return $this;
    }

    protected function prepareLocalizedQuery(Builder $query, ?Closure $modifyQueryUsing = null, ?string $search = null): Builder
    {
        if (method_exists($query->getModel(), 'scopeWithLocalizedName')) {
            $query->withLocalizedName();
        }

        $query = $modifyQueryUsing
            ? ($this->evaluate($modifyQueryUsing, ['query' => $query, 'search' => $search]) ?? $query)
            : $query;

        if (
            method_exists($query->getModel(), 'scopeWithLocalizedName')
            && empty($query->getQuery()->orders)
        ) {
            $query
                ->orderBy('localized_name')
                ->orderBy($query->getModel()->qualifyColumn($query->getModel()->getKeyName()));
        }

        return $query;
    }

    protected function getLocalizedOptionLabel(Model $record): string
    {
        $localizedName = $record->getAttribute('localized_name');

        if (filled($localizedName)) {
            return (string) $localizedName;
        }

        if ($this->fallbackLabelUsing) {
            return (string) $this->evaluate(
                $this->fallbackLabelUsing,
                namedInjections: ['record' => $record],
                typedInjections: [Model::class => $record, $record::class => $record],
            );
        }

        return (string) $record->getKey();
    }

    protected function applyLocalizedSearchConstraint(Builder $query, string $search, Relation $relationship): Builder
    {
        $qualifiedRelatedKeyName = $this->getQualifiedRelatedKeyNameForRelationship($relationship);

        return $query->where(function (Builder $query) use ($qualifiedRelatedKeyName, $search): void {
            $query->where($qualifiedRelatedKeyName, 'like', "%{$search}%");

            if (method_exists($query->getModel(), 'scopeSearchLocalizedName')) {
                $query->orWhere(fn (Builder $query): Builder => $query->searchLocalizedName($search));
            }
        });
    }
}
