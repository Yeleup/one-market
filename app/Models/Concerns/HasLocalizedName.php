<?php

namespace App\Models\Concerns;

use App\Support\Translations\CurrentTranslationResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasLocalizedName
{
    public function getLocalizedNameAttribute(): ?string
    {
        $localizedName = $this->getAttributeFromArray('localized_name');

        if (filled($localizedName)) {
            return (string) $localizedName;
        }

        return CurrentTranslationResolver::name($this);
    }

    public function scopeWithLocalizedName(Builder $query, string $column = 'localized_name'): Builder
    {
        return $query->addSelect([
            $column => $query->getModel()->localizedNameSubquery(),
        ]);
    }

    public function scopeSearchLocalizedName(Builder $query, string $search): Builder
    {
        if (blank($search)) {
            return $query;
        }

        return $query->whereHas('translations', function (Builder $query) use ($search): void {
            $query->where('name', 'like', "%{$search}%");
        });
    }

    protected function localizedNameSubquery(): Builder
    {
        /** @var HasMany $translations */
        $translations = $this->translations();
        $translationTable = $translations->getRelated()->getTable();
        $preferredLanguageIds = CurrentTranslationResolver::preferredLanguageIds();

        $query = $translations->getRelated()
            ->newQuery()
            ->select("{$translationTable}.name")
            ->whereColumn(
                $translations->getQualifiedForeignKeyName(),
                $translations->getQualifiedParentKeyName(),
            );

        if ($preferredLanguageIds !== []) {
            $cases = collect($preferredLanguageIds)
                ->values()
                ->map(
                    fn (int $languageId, int $index): string => "when {$translationTable}.language_id = ? then {$index}",
                )
                ->implode(' ');

            $query->orderByRaw(
                "case {$cases} else ? end",
                [...$preferredLanguageIds, count($preferredLanguageIds)],
            );
        }

        return $query
            ->orderBy("{$translationTable}.id")
            ->limit(1);
    }
}
