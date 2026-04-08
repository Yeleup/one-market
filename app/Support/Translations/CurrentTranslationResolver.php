<?php

namespace App\Support\Translations;

use App\Models\Language;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

final class CurrentTranslationResolver
{
    public static function name(Model $record, string $relationshipPath = 'translations'): ?string
    {
        /** @var Collection<int, Model>|null $translations */
        $translations = data_get($record, $relationshipPath);

        if (! $translations instanceof Collection || $translations->isEmpty()) {
            return null;
        }

        /** @var Model|null $translation */
        $translation = collect(self::preferredLanguageIds())
            ->map(fn (int $languageId): ?Model => $translations->firstWhere('language_id', $languageId))
            ->first(fn (?Model $translation): bool => $translation instanceof Model);

        return $translation?->getAttribute('name') ?? $translations->first()?->getAttribute('name');
    }

    public static function languageId(): ?int
    {
        return Arr::first(self::preferredLanguageIds());
    }

    /**
     * @return array<int, int>
     */
    public static function preferredLanguageIds(): array
    {
        $currentLanguageId = Language::query()
            ->where('code', App::currentLocale())
            ->value('id');

        $defaultLanguageId = Language::query()
            ->where('is_default', true)
            ->value('id');

        return collect([$currentLanguageId, $defaultLanguageId])
            ->filter(fn (?int $languageId): bool => $languageId !== null)
            ->unique()
            ->values()
            ->all();
    }
}
