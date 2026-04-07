<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('is_active');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('image');
        });

        $this->backfillSharedSlugs('categories', 'category_translations', 'category_id', 'category');
        $this->backfillSharedSlugs('products', 'product_translations', 'product_id', 'product');

        Schema::table('categories', function (Blueprint $table) {
            $table->unique('slug');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique('slug');
        });

        Schema::table('category_translations', function (Blueprint $table) {
            $table->dropColumn('slug');
        });

        Schema::table('product_translations', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }

    public function down(): void
    {
        Schema::table('category_translations', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        Schema::table('product_translations', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('description');
        });

        $this->restoreTranslationSlugs('categories', 'category_translations', 'category_id');
        $this->restoreTranslationSlugs('products', 'product_translations', 'product_id');

        Schema::table('category_translations', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }

    private function backfillSharedSlugs(
        string $ownerTable,
        string $translationTable,
        string $foreignKey,
        string $fallbackPrefix,
    ): void {
        $defaultLanguageId = DB::table('languages')
            ->where('is_default', true)
            ->value('id');

        $usedSlugs = [];

        DB::table($ownerTable)
            ->select('id')
            ->orderBy('id')
            ->get()
            ->each(function (object $record) use (
                $defaultLanguageId,
                $fallbackPrefix,
                $foreignKey,
                $ownerTable,
                $translationTable,
                &$usedSlugs
            ): void {
                $translations = DB::table($translationTable)
                    ->select('language_id', 'name', 'slug')
                    ->where($foreignKey, $record->id)
                    ->orderBy('id')
                    ->get();

                $preferredTranslation = ($defaultLanguageId
                    ? $translations->first(
                        fn (object $translation): bool => $translation->language_id === $defaultLanguageId
                            && filled($translation->slug),
                    )
                    : null)
                    ?? $translations->first(fn (object $translation): bool => filled($translation->slug))
                    ?? ($defaultLanguageId ? $translations->firstWhere('language_id', $defaultLanguageId) : null)
                    ?? $translations->first();

                $baseSlug = Str::slug(trim((string) ($preferredTranslation->slug ?? '')));

                if (blank($baseSlug)) {
                    $baseSlug = Str::slug(trim((string) ($preferredTranslation->name ?? '')));
                }

                if (blank($baseSlug)) {
                    $baseSlug = "{$fallbackPrefix}-{$record->id}";
                }

                $slug = $this->makeUniqueSlug($baseSlug, $usedSlugs, (int) $record->id);

                DB::table($ownerTable)
                    ->where('id', $record->id)
                    ->update(['slug' => $slug]);
            });
    }

    private function restoreTranslationSlugs(string $ownerTable, string $translationTable, string $foreignKey): void
    {
        DB::table($translationTable)
            ->join($ownerTable, "{$ownerTable}.id", '=', "{$translationTable}.{$foreignKey}")
            ->update([
                "{$translationTable}.slug" => DB::raw("{$ownerTable}.slug"),
            ]);
    }

    /**
     * @param  array<string, bool>  $usedSlugs
     */
    private function makeUniqueSlug(string $baseSlug, array &$usedSlugs, int $recordId): string
    {
        $slug = $baseSlug;

        if (isset($usedSlugs[$slug])) {
            $slug = "{$baseSlug}-{$recordId}";
        }

        $usedSlugs[$slug] = true;

        return $slug;
    }
};
