<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Enums;

enum MovementStatus: string
{
    case PENDING    = 'pending';
    case IN_TRANSIT = 'in_transit';
    case COMPLETED  = 'completed';
    case CANCELLED  = 'cancelled';
}
