<?php

namespace App\Observers;

use App\Models\Order;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $order->logHistory('order_created', "Order {$order->order_number} created");
    }

    public function updated(Order $order): void
    {
        if ($order->wasChanged('status')) {
            $oldStatus = $order->getOriginal('status');
            $oldStatusValue = $oldStatus instanceof \App\Enums\OrderStatus ? $oldStatus->value : $oldStatus;
            
            $order->logHistory(
                'status_changed',
                "Status changed from {$oldStatusValue} to {$order->status->value}",
                [
                    'old_status' => $oldStatusValue,
                    'new_status' => $order->status->value,
                ]
            );
        }
    }

    public function deleted(Order $order): void
    {
        $order->logHistory('order_deleted', "Order {$order->order_number} deleted");
    }
}
