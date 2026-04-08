<?php

namespace App\Filament\Concerns;

use App\Actions\Bonuses\AccrueBonusesAction;
use App\Actions\Bonuses\ManualDebitBonusesAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Validation\Rules\Password;
use Throwable;

trait HasClientHeaderActions
{
    /**
     * @return array<int, Action>
     */
    protected function getClientHeaderActions(): array
    {
        return [
            $this->makeAccrueBonusesAction(),
            $this->makeManualDebitBonusesAction(),
            $this->makeResetClientPasswordAction(),
            $this->makeToggleClientActiveAction(),
        ];
    }

    protected function makeAccrueBonusesAction(): Action
    {
        return Action::make('accrueBonuses')
            ->label(__('admin.actions.client.accrue_bonuses'))
            ->icon('heroicon-o-plus-circle')
            ->color('success')
            ->form([
                TextInput::make('amount')
                    ->label(__('admin.common.fields.amount'))
                    ->numeric()
                    ->required()
                    ->minValue(1),
                Textarea::make('comment')
                    ->label(__('admin.common.fields.comment'))
                    ->rows(3),
            ])
            ->action(function (array $data): void {
                try {
                    app(AccrueBonusesAction::class)->handle(
                        client: $this->getRecord(),
                        amount: (int) $data['amount'],
                        performedByUserId: auth()->id(),
                        comment: $data['comment'] ?: null,
                    );
                } catch (Throwable $exception) {
                    Notification::make()
                        ->danger()
                        ->title(__('admin.actions.client.notifications.accrue_failed'))
                        ->body($exception->getMessage())
                        ->send();

                    return;
                }

                $this->refreshRecordState(['bonus_balance', 'bonus_reserved']);
                $this->dispatch('client-bonus-transactions-updated');

                Notification::make()
                    ->success()
                    ->title(__('admin.actions.client.notifications.accrued'))
                    ->send();
            });
    }

    protected function makeManualDebitBonusesAction(): Action
    {
        return Action::make('manualDebitBonuses')
            ->label(__('admin.actions.client.debit_bonuses'))
            ->icon('heroicon-o-minus-circle')
            ->color('danger')
            ->form([
                TextInput::make('amount')
                    ->label(__('admin.common.fields.amount'))
                    ->numeric()
                    ->required()
                    ->minValue(1),
                Textarea::make('comment')
                    ->label(__('admin.common.fields.comment'))
                    ->rows(3),
            ])
            ->action(function (array $data): void {
                try {
                    app(ManualDebitBonusesAction::class)->handle(
                        client: $this->getRecord(),
                        amount: (int) $data['amount'],
                        performedByUserId: auth()->id(),
                        comment: $data['comment'] ?: null,
                    );
                } catch (Throwable $exception) {
                    Notification::make()
                        ->danger()
                        ->title(__('admin.actions.client.notifications.debit_failed'))
                        ->body($exception->getMessage())
                        ->send();

                    return;
                }

                $this->refreshRecordState(['bonus_balance', 'bonus_reserved']);
                $this->dispatch('client-bonus-transactions-updated');

                Notification::make()
                    ->success()
                    ->title(__('admin.actions.client.notifications.debited'))
                    ->send();
            });
    }

    protected function makeResetClientPasswordAction(): Action
    {
        return Action::make('resetClientPassword')
            ->label(__('admin.actions.client.reset_password'))
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

                $this->refreshRecordState();

                Notification::make()
                    ->success()
                    ->title(__('admin.actions.client.notifications.password_updated'))
                    ->send();
            });
    }

    protected function makeToggleClientActiveAction(): Action
    {
        return Action::make('toggleClientActive')
            ->label(fn (): string => $this->getRecord()->is_active ? __('admin.common.actions.deactivate') : __('admin.common.actions.activate'))
            ->icon(fn (): string => $this->getRecord()->is_active ? 'heroicon-o-no-symbol' : 'heroicon-o-check-circle')
            ->color(fn (): string => $this->getRecord()->is_active ? 'danger' : 'success')
            ->requiresConfirmation()
            ->action(function (): void {
                $this->getRecord()->update([
                    'is_active' => ! $this->getRecord()->is_active,
                ]);

                $this->refreshRecordState(['is_active']);

                Notification::make()
                    ->success()
                    ->title(__('admin.actions.client.notifications.status_updated'))
                    ->send();
            });
    }

    /**
     * @param  array<string>  $statePaths
     */
    protected function refreshRecordState(array $statePaths = []): void
    {
        $this->record = $this->getRecord()->fresh();

        if ($statePaths !== [] && method_exists($this, 'refreshFormData')) {
            $this->refreshFormData($statePaths);

            return;
        }

        if ($statePaths !== [] && method_exists($this, 'fillForm')) {
            $this->fillForm();
        }
    }
}
