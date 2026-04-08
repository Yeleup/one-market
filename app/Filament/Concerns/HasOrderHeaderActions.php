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
            $this->makeDownloadPdfAction(),
            $this->makeMoveToProcessingAction(),
            $this->makeMoveToDeliveredAction(),
            $this->makeCancelOrderAction(),
            OrderResource::makeDeleteAction()
                ->successRedirectUrl(OrderResource::getUrl('index')),
        ];
    }

    protected function makeDownloadPdfAction(): Action
    {
        return Action::make('downloadOrderPdf')
            ->label('PDF')
            ->color('success')
            ->icon('heroicon-o-document-arrow-down')
            ->visible(fn (): bool => $this->getRecord()->status === OrderStatus::Processing)
            ->url(fn (): string => route('orders.pdf', $this->getRecord()))
            ->openUrlInNewTab();
    }

    protected function makeMoveToProcessingAction(): Action
    {
        return Action::make('markOrderAsProcessing')
            ->label(__('admin.actions.order.move_to_processing'))
            ->color('gray')
            ->icon('heroicon-o-arrow-path')
            ->visible(fn (): bool => $this->getRecord()->status === OrderStatus::New)
            ->requiresConfirmation()
            ->action(fn (): mixed => $this->transitionOrderStatus(
                OrderStatus::Processing,
                __('admin.actions.order.notifications.processing'),
            )->send());
    }

    protected function makeMoveToDeliveredAction(): Action
    {
        return Action::make('markOrderAsDelivered')
            ->label(__('admin.actions.order.move_to_delivered'))
            ->color('success')
            ->icon('heroicon-o-check-circle')
            ->visible(fn (): bool => $this->getRecord()->status === OrderStatus::Processing)
            ->requiresConfirmation()
            ->action(fn (): mixed => $this->transitionOrderStatus(
                OrderStatus::Delivered,
                __('admin.actions.order.notifications.delivered'),
            )->send());
    }

    protected function makeCancelOrderAction(): Action
    {
        return Action::make('cancelOrder')
            ->label(__('admin.actions.order.cancel'))
            ->color('danger')
            ->icon('heroicon-o-x-circle')
            ->visible(fn (): bool => ! in_array($this->getRecord()->status, [OrderStatus::Delivered, OrderStatus::Cancelled], true))
            ->requiresConfirmation()
            ->action(fn (): mixed => $this->transitionOrderStatus(
                OrderStatus::Cancelled,
                __('admin.actions.order.notifications.cancelled'),
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
                ->title(__('admin.actions.order.notifications.failed'))
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
