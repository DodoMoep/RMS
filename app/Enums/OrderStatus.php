<?php

namespace App\Enums;

enum OrderStatus: string
{
    case NEW = 'new';
    case IN_PROGRESS = 'in_progress';
    case PACKED = 'packed';
    case IN_DELIVERY = 'in_delivery';
    case DELIVERED = 'delivered';

    public function label(): string
    {
        return match($this) {
            self::NEW => __('orders.statuses.new'),
            self::IN_PROGRESS => __('orders.statuses.in_progress'),
            self::PACKED => __('orders.statuses.packed'),
            self::IN_DELIVERY => __('orders.statuses.in_delivery'),
            self::DELIVERED => __('orders.statuses.delivered'),
        };
    }

    public function color(): string
    {
        return match($this) {
            self::NEW => 'bg-primary text-white',
            self::IN_PROGRESS => 'bg-warning text-dark',
            self::PACKED => 'bg-info text-white',
            self::IN_DELIVERY => 'bg-secondary text-white',
            self::DELIVERED => 'bg-success text-white',
        };
    }

    public function canTransitionTo(OrderStatus $status): bool
    {
        return match($this) {
            self::NEW => in_array($status, [self::IN_PROGRESS]),
            self::IN_PROGRESS => in_array($status, [self::PACKED]),
            self::PACKED => in_array($status, [self::IN_DELIVERY]),
            self::IN_DELIVERY => in_array($status, [self::DELIVERED]),
            self::DELIVERED => false,
        };
    }
}
