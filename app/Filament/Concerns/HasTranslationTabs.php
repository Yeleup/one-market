<?php

namespace App\Filament\Concerns;

use App\Models\Language;
use Closure;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

trait HasTranslationTabs
{
    /**
     * @return Collection<int, Language>
     */
    protected static function getFormLanguages(): Collection
    {
        /** @var Collection<int, Language> $languages */
        $languages = once(fn (): Collection => Language::query()
            ->active()
            ->ordered()
            ->get());

        return $languages;
    }

    /**
     * @param  Closure(Language, string): array<int, mixed>  $schema
     */
    protected static function makeTranslationTabs(Closure $schema): Tabs
    {
        $languages = static::getFormLanguages();
        $activeTab = $languages->search(fn (Language $language): bool => $language->is_default);

        return Tabs::make('Translations')
            ->tabs(
                $languages
                    ->map(
                        fn (Language $language): Tab => Tab::make(Str::upper($language->code))
                            ->schema($schema($language, "translations.{$language->id}"))
                    )
                    ->all(),
            )
            ->activeTab(($activeTab === false ? 0 : $activeTab) + 1)
            ->columnSpanFull();
    }
}
