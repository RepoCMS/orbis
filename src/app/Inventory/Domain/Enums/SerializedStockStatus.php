<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Enums;

enum SerializedStockStatus: string
{
    case AVAILABLE    = 'disponible';
    case RESERVED     = 'reservado';
    case DAMAGED      = 'dañado';
    case SUSPECT      = 'sospechoso';
    case DECOMMISSIONED = 'dado de baja';
}
