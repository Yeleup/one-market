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
