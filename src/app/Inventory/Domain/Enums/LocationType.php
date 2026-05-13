<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Enums;

/**
 * Tipo de ubicación física de inventario.
 *
 * WAREHOUSE  — Almacén/bodega donde se guarda stock disponible.
 * LABORATORY — Laboratorio al que se envían piezas dañadas para reparación o transformación.
 */
enum LocationType: string
{
    case WAREHOUSE  = 'warehouse';
    case LABORATORY = 'laboratory';
    case BACKPACK   = 'backpack';
}
