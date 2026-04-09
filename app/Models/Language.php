<?php

namespace App\Models;

use Database\Factories\LanguageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['code', 'name', 'is_default', 'is_active', 'sort_order'])]
class Language extends Model
{
    /** @use HasFactory<LanguageFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function (Language $language): void {
            if (! $language->is_default) {
                return;
            }

            static::query()
                ->whereKeyNot($language->getKey())
                ->where('is_default', true)
                ->update(['is_default' => false]);
        });
    }

    public static function resolveAdminLocale(?string $code = null): string
    {
        $locale = Str::of((string) ($code ?? static::query()
            ->where('is_default', true)
            ->value('code') ?? config('app.locale')))
            ->replace('-', '_')
            ->lower()
            ->toString();

        if (is_file(lang_path("{$locale}/admin.php"))) {
            return $locale;
        }

        return (string) config('app.fallback_locale', 'en');
    }

    public static function resolveStorefrontLocale(?string $code = null): string
    {
        $requestedLocale = Str::of((string) $code)
            ->replace('-', '_')
            ->lower()
            ->toString();

        $activeLanguages = static::query()
            ->active()
            ->ordered()
            ->get(['code', 'is_default']);

        if ($requestedLocale !== '') {
            $matchingLanguage = $activeLanguages->firstWhere('code', $requestedLocale);

            if ($matchingLanguage !== null) {
                return $matchingLanguage->code;
            }
        }

        return (string) (
            $activeLanguages->firstWhere('is_default', true)?->code
            ?? $activeLanguages->first()?->code
            ?? static::resolveAdminLocale(config('app.locale'))
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<Language>  $query
     * @return Builder<Language>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Language>  $query
     * @return Builder<Language>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
