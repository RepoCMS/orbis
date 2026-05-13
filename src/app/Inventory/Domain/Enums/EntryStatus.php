<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Enums;

/**
 * Estado de un registro de entrada de inventario (Entry).
 *
 * DRAFT     — En construcción; los ítems pueden modificarse.
 * CONFIRMED — Confirmada; el stock fue cargado al inventario.
 * CANCELLED — Cancelada; no se cargó stock.
 */
enum EntryStatus: string
{
    case DRAFT     = 'draft';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
}
