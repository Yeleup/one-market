<?php

namespace App\Filament\Concerns;

use App\Actions\Orders\ChangeOrderStatusAction;
use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;

trait HasOrderHeaderActions
{
    /**
     * @return array<int, Action>
     */
    protected function getOrderHeaderActions(): array
    {
        return [
            $this->makeMoveToProcessingAction(),
            $this->makeMoveToReadyForDeliveryAction(),
            $this->makeMoveToDeliveredAction(),
            $this->makeCancelOrderAction(),
            OrderResource::makeDeleteAction()
                ->successRedirectUrl(OrderResource::getUrl('index')),
        ];
    }

    protected function makeMoveToProcessingAction(): Action
    {
        return Action::make('markOrderAsProcessing')
            ->label('В обработку')
            ->color('gray')
            ->icon('heroicon-o-arrow-path')
            ->visible(fn (): bool => $this->getRecord()->status === OrderStatus::New)
            ->requiresConfirmation()
            ->action(fn (): mixed => $this->transitionOrderStatus(
                OrderStatus::Processing,
                'Статус заказа изменён на "В обработке".',
            )->send());
    }

    protected function makeMoveToReadyForDeliveryAction(): Action
    {
        return Action::make('markOrderAsReadyForDelivery')
            ->label('Готов к доставке')
            ->color('info')
            ->icon('heroicon-o-truck')
            ->visible(fn (): bool => $this->getRecord()->status === OrderStatus::Processing)
            ->requiresConfirmation()
            ->action(fn (): mixed => $this->transitionOrderStatus(
                OrderStatus::ReadyForDelivery,
                'Статус заказа изменён на "Готов к доставке".',
            )->send());
    }

    protected function makeMoveToDeliveredAction(): Action
    {
        return Action::make('markOrderAsDelivered')
            ->label('Доставлено')
            ->color('success')
            ->icon('heroicon-o-check-circle')
            ->visible(fn (): bool => $this->getRecord()->status === OrderStatus::ReadyForDelivery)
            ->requiresConfirmation()
            ->action(fn (): mixed => $this->transitionOrderStatus(
                OrderStatus::Delivered,
                'Заказ отмечен как доставленный.',
            )->send());
    }

    protected function makeCancelOrderAction(): Action
    {
        return Action::make('cancelOrder')
            ->label('Отменить')
            ->color('danger')
            ->icon('heroicon-o-x-circle')
            ->visible(fn (): bool => ! in_array($this->getRecord()->status, [OrderStatus::Delivered, OrderStatus::Cancelled], true))
            ->requiresConfirmation()
            ->action(fn (): mixed => $this->transitionOrderStatus(
                OrderStatus::Cancelled,
                'Заказ отменён.',
            )->send());
    }

    protected function transitionOrderStatus(OrderStatus $targetStatus, string $successMessage): Notification
    {
        try {
            app(ChangeOrderStatusAction::class)->handle(
                order: $this->getRecord(),
                targetStatus: $targetStatus,
                changedByUserId: auth()->id(),
            );
        } catch (Throwable $exception) {
            return Notification::make()
                ->danger()
                ->title('Не удалось изменить статус заказа.')
                ->body($exception->getMessage());
        }

        $this->refreshRecordState();

        return Notification::make()
            ->success()
            ->title($successMessage);
    }

    protected function refreshRecordState(): void
    {
        $this->record = $this->getRecord()->fresh();

        if (method_exists($this, 'fillForm')) {
            $this->fillForm();
        }
    }
}
