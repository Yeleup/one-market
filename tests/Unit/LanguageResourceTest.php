<?php

use App\Filament\Resources\LanguageResource;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

uses(TestCase::class);

it('does not include lang vendor directory in available language options', function () {
    File::ensureDirectoryExists(lang_path('zz_test_locale'));
    File::ensureDirectoryExists(lang_path('vendor'));

    try {
        $resource = new class extends LanguageResource
        {
            /**
             * @return array<string, string>
             */
            public static function availableLanguageOptions(): array
            {
                return self::getAvailableLanguageOptions();
            }
        };

        $options = $resource::availableLanguageOptions();

        expect($options)
            ->toHaveKey('en')
            ->toHaveKey('kk')
            ->toHaveKey('ru')
            ->toHaveKey('zz_test_locale')
            ->not->toHaveKey('vendor');
    } finally {
        File::deleteDirectory(lang_path('zz_test_locale'));
    }
});
