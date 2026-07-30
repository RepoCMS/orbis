<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Enums;

/**
 * Prioridad de una reparación en el laboratorio.
 *
 * LOW       — Sin urgencia, puede programarse.
 * NORMAL    — Plazo estándar de reparación.
 * HIGH      — Requiere atención prioritaria.
 * CRITICAL  — Urgencia máxima (ej. SLA cercano al breach).
 */
enum RepairPriority: string
{
    case LOW      = 'low';
    case NORMAL   = 'normal';
    case HIGH     = 'high';
    case CRITICAL = 'critical';
}
