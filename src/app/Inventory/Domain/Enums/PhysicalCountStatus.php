<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Enums;

/**
 * Estado de un conteo físico de inventario.
 *
 * DRAFT             — En preparación; se pueden agregar/modificar ítems.
 * PENDING_APPROVAL  — Enviado para aprobación; ya no se puede modificar.
 * APPROVED          — Aprobado; se generan los ajustes de stock.
 * REJECTED          — Rechazado; no se generan ajustes.
 */
enum PhysicalCountStatus: string
{
    case DRAFT            = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED         = 'approved';
    case REJECTED         = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT            => 'Borrador',
            self::PENDING_APPROVAL => 'Pendiente de aprobación',
            self::APPROVED         => 'Aprobado',
            self::REJECTED         => 'Rechazado',
        };
    }

    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    public function isPendingApproval(): bool
    {
        return $this === self::PENDING_APPROVAL;
    }

    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }

    public function isTerminal(): bool
    {
        return $this === self::APPROVED || $this === self::REJECTED;
    }
}
