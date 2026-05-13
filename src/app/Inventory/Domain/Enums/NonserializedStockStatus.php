<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Enums;

enum NonserializedStockStatus: string
{
    case AVAILABLE      = 'disponible';
    case RESERVED       = 'reservado';
    case DAMAGED        = 'dañado';
    case DECOMMISSIONED = 'dado de baja';
}
