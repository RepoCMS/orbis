<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Enums;

enum SerialHistoryReason: string
{
    case REPAIR            = 'repair';
    case TRANSFORMATION    = 'transformation';
    case SERIAL_ASSIGNMENT = 'serial_assignment';
}
