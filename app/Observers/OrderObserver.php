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
            $order->logHistory(
                'status_changed',
                "Status changed from {$order->getOriginal('status')} to {$order->status->value}",
                [
                    'old_status' => $order->getOriginal('status'),
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
