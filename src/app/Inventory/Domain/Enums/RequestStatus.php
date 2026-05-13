<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Enums;

/**
 * Estado de una solicitud de movimiento de stock.
 *
 * PENDING                       — Enviada, esperando aprobación.
 * APPROVED                      — Aprobada, pendiente de ejecutar el movimiento.
 * AWAITING_TECHNICIAN_ACCEPTANCE — Aprobada por bodeguero, pendiente de aceptación del técnico.
 * REJECTED                      — Rechazada, no se ejecutará.
 * FULFILLED                     — Movimiento generado y completado.
 * CANCELLED                     — Cancelada por el solicitante antes de aprobación.
 */
enum RequestStatus: string
{
    case PENDING                       = 'pending';
    case APPROVED                      = 'approved';
    case AWAITING_TECHNICIAN_ACCEPTANCE = 'awaiting_technician_acceptance';
    case REJECTED                      = 'rejected';
    case FULFILLED                     = 'fulfilled';
    case CANCELLED                     = 'cancelled';
}
