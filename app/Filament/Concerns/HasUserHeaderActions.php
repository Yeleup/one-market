<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Validation\Rules\Password;

trait HasUserHeaderActions
{
    /**
     * @return array<int, Action>
     */
    protected function getUserHeaderActions(): array
    {
        return [
            $this->makeResetUserPasswordAction(),
            $this->makeToggleUserActiveAction(),
        ];
    }

    protected function makeResetUserPasswordAction(): Action
    {
        return Action::make('resetUserPassword')
            ->label('Сбросить пароль')
            ->icon('heroicon-o-key')
            ->color('gray')
            ->form([
                TextInput::make('password')
                    ->label('Новый пароль')
                    ->password()
                    ->required()
                    ->rule(Password::defaults()),
            ])
            ->action(function (array $data): void {
                $this->getRecord()->update([
                    'password' => $data['password'],
                ]);

                $this->record = $this->getRecord()->fresh();

                if (method_exists($this, 'fillForm')) {
                    $this->fillForm();
                }

                Notification::make()
                    ->success()
                    ->title('Пароль пользователя обновлён.')
                    ->send();
            });
    }

    protected function makeToggleUserActiveAction(): Action
    {
        return Action::make('toggleUserActive')
            ->label(fn (): string => $this->getRecord()->is_active ? 'Деактивировать' : 'Активировать')
            ->icon(fn (): string => $this->getRecord()->is_active ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
            ->color(fn (): string => $this->getRecord()->is_active ? 'danger' : 'success')
            ->requiresConfirmation()
            ->action(function (): void {
                $this->getRecord()->update([
                    'is_active' => ! $this->getRecord()->is_active,
                ]);

                $this->record = $this->getRecord()->fresh();

                if (method_exists($this, 'fillForm')) {
                    $this->fillForm();
                }

                Notification::make()
                    ->success()
                    ->title('Статус пользователя обновлён.')
                    ->send();
            });
    }
}
