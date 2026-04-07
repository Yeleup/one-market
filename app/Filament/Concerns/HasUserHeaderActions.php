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
            ->label(__('admin.actions.user.reset_password'))
            ->icon('heroicon-o-key')
            ->color('gray')
            ->form([
                TextInput::make('password')
                    ->label(__('admin.common.fields.new_password'))
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
                    ->title(__('admin.actions.user.notifications.password_updated'))
                    ->send();
            });
    }

    protected function makeToggleUserActiveAction(): Action
    {
        return Action::make('toggleUserActive')
            ->label(fn (): string => $this->getRecord()->is_active ? __('admin.common.actions.deactivate') : __('admin.common.actions.activate'))
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
                    ->title(__('admin.actions.user.notifications.status_updated'))
                    ->send();
            });
    }
}
