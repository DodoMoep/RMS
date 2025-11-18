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
            self::NEW => 'Neu',
            self::IN_PROGRESS => 'In Bearbeitung',
            self::PACKED => 'Verpackt',
            self::IN_DELIVERY => 'In Zustellung',
            self::DELIVERED => 'Zugestellt',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::NEW => 'bg-blue-100 text-blue-800',
            self::IN_PROGRESS => 'bg-yellow-100 text-yellow-800',
            self::PACKED => 'bg-purple-100 text-purple-800',
            self::IN_DELIVERY => 'bg-orange-100 text-orange-800',
            self::DELIVERED => 'bg-green-100 text-green-800',
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
