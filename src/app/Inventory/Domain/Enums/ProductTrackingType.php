<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Enums;

/**
 * Tipo de rastreo de un producto.
 *
 * SERIALIZED     — Producto con seguimiento por serie.
 * NON_SERIALIZED — Producto sin seguimiento por serie.
 */
enum ProductTrackingType: string
{
    case SERIALIZED     = 'serialized';
    case NON_SERIALIZED = 'non_serialized';
}
