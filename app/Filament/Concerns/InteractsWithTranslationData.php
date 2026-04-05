<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

trait InteractsWithTranslationData
{
    /**
     * @var array<int|string, array<string, mixed>>
     */
    protected array $translationsData = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['translations'] = $this->getTranslationsFormData($this->getRecord());

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->extractTranslationsFromData($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->extractTranslationsFromData($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $record = parent::handleRecordCreation($data);

        $this->syncTranslations($record);

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record = parent::handleRecordUpdate($record, $data);

        $this->syncTranslations($record);

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function extractTranslationsFromData(array $data): array
    {
        /** @var array<int|string, array<string, mixed>> $translations */
        $translations = $data['translations'] ?? [];

        $this->translationsData = $translations;

        unset($data['translations']);

        return $data;
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    protected function getTranslationsFormData(Model $record): array
    {
        $resource = static::getResource();
        $relationshipName = $resource::getTranslationRelationshipName();
        $attributes = $resource::getTranslatableAttributes();

        $record->loadMissing($relationshipName);

        return $record
            ->getRelation($relationshipName)
            ->mapWithKeys(
                fn (Model $translation): array => [
                    $translation->getAttribute('language_id') => Arr::only(
                        $translation->attributesToArray(),
                        $attributes,
                    ),
                ],
            )
            ->all();
    }

    protected function syncTranslations(Model $record): void
    {
        $resource = static::getResource();
        $relationshipName = $resource::getTranslationRelationshipName();
        $attributes = $resource::getTranslatableAttributes();
        $relationship = $record->{$relationshipName}();
        $existingTranslations = $relationship->get()->keyBy('language_id');

        foreach ($this->translationsData as $languageId => $translationData) {
            $payload = collect(Arr::only($translationData, $attributes))
                ->map(fn (mixed $value): mixed => is_string($value) ? trim($value) : $value)
                ->all();

            if (collect($payload)->every(fn (mixed $value): bool => blank($value))) {
                $existingTranslations->get($languageId)?->delete();

                continue;
            }

            $relationship->updateOrCreate(
                ['language_id' => $languageId],
                $payload,
            );
        }
    }
}
