<?php

namespace App\Filament\Resources\LanguageResource\Pages;

use App\Filament\Resources\LanguageResource;
use App\Models\Language;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditLanguage extends EditRecord
{
    protected static string $resource = LanguageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('setDefaultLanguage')
                ->label('Сделать языком по умолчанию')
                ->icon('heroicon-o-star')
                ->color('warning')
                ->visible(fn (): bool => ! $this->getRecord()->is_default)
                ->requiresConfirmation()
                ->action(function (): void {
                    DB::transaction(function (): void {
                        Language::query()->update(['is_default' => false]);

                        $this->getRecord()->update(['is_default' => true]);
                    });

                    $this->record = $this->getRecord()->fresh();
                    $this->fillForm();

                    Notification::make()
                        ->success()
                        ->title('Язык по умолчанию обновлён.')
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
