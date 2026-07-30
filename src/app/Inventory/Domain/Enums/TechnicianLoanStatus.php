<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Enums;

/**
 * Estado de un préstamo entre técnicos.
 *
 * PENDING_LOAN — Solicitud de préstamo creada, pendiente de aprobación.
 * APPROVED     — Préstamo aprobado, se ha creado el Movement(LOAN).
 * REJECTED     — Préstamo rechazado por el responsable.
 * CANCELLED    — Préstamo cancelado antes de ser aprobado.
 */
enum TechnicianLoanStatus: string
{
    case PENDING_LOAN = 'pending_loan';
    case APPROVED     = 'approved';
    case REJECTED     = 'rejected';
    case CANCELLED    = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING_LOAN => 'Pendiente de aprobación',
            self::APPROVED     => 'Aprobado',
            self::REJECTED     => 'Rechazado',
            self::CANCELLED    => 'Cancelado',
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::APPROVED, self::REJECTED, self::CANCELLED => true,
            self::PENDING_LOAN => false,
        };
    }
}
