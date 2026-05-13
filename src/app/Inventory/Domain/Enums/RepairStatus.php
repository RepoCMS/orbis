<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Enums;

/**
 * Estado de una tarea de reparación de repuesto.
 *
 * PENDING      — Asignada, aún no iniciada.
 * IN_PROGRESS  — Reparación en curso en el laboratorio.
 * COMPLETED    — Reparación exitosa; la pieza vuelve al inventario disponible.
 * UNREPAIRABLE — La pieza no pudo repararse; se retira del inventario.
 */
enum RepairStatus: string
{
    case PENDING      = 'pending';
    case IN_PROGRESS  = 'in_progress';
    case COMPLETED    = 'completed';
    case UNREPAIRABLE = 'unrepairable';
}
