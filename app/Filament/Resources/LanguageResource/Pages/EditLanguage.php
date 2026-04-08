<?php

namespace App\Filament\Resources\LanguageResource\Pages;

use App\Filament\Resources\LanguageResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLanguage extends EditRecord
{
    protected static string $resource = LanguageResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('setDefaultLanguage')
                ->label(__('admin.resources.language.actions.set_default'))
                ->icon('heroicon-o-star')
                ->color('warning')
                ->visible(fn (): bool => ! $this->getRecord()->is_default)
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->getRecord()->update(['is_default' => true]);

                    $this->record = $this->getRecord()->fresh();
                    $this->fillForm();

                    Notification::make()
                        ->success()
                        ->title(__('admin.resources.language.notifications.default_updated'))
                        ->send();

                    $this->redirect(LanguageResource::getUrl('index'));
                }),
            DeleteAction::make(),
        ];
    }
}
