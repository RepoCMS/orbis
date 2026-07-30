<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Enums;

enum RequestItemStatus: string
{
    case PENDING   = 'pending';
    case FULFILLED = 'fulfilled';
    case CANCELLED = 'cancelled';
}
