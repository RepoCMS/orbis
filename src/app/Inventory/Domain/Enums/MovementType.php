<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Enums;

/**
 * Tipo de movimiento de inventario.
 *
 * ENTRY    — Ingreso de stock desde fuera del sistema (compra, donación).
 *            sourceLocationId = null.
 * TRANSFER — Traslado interno entre ubicaciones.
 *            sourceLocationId != null, destinationLocationId != null.
 * EXIT     — Salida de stock fuera del sistema (uso en campo, baja).
 *            destinationLocationId = null.
 */
enum MovementType: string
{
    case ENTRY    = 'entry';
    case TRANSFER = 'transfer';
    case EXIT     = 'exit';
}
