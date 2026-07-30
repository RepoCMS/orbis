<?php

declare(strict_types=1);

namespace App\Expense\Domain\Enums;

/**
 * Status del ciclo de vida de una liquidación de gastos (OperatingExpense).
 *
 * @see \App\Expense\Domain\Entities\OperatingExpense
 */
enum ExpenseStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::SUBMITTED => 'Enviado a revisión',
            self::APPROVED => 'Aprobado',
            self::REJECTED => 'Rechazado',
        };
    }

    public function canSubmit(): bool
    {
        return $this === self::DRAFT || $this === self::REJECTED;
    }

    public function canApprove(): bool
    {
        return $this === self::SUBMITTED;
    }

    public function canEdit(): bool
    {
        return $this === self::DRAFT || $this === self::REJECTED;
    }

    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this === self::SUBMITTED;
    }

    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this === self::REJECTED;
    }
}
