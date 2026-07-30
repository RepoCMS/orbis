<?php

declare(strict_types=1);

namespace App\Expense\Domain\Enums;

/**
 * Status individual de cada item de gasto dentro de una liquidación.
 *
 * @see \App\Expense\Domain\Entities\ExpenseItem
 */
enum ExpenseItemStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::APPROVED => 'Aprobado',
            self::REJECTED => 'Rechazado',
        };
    }

    public function isReviewed(): bool
    {
        return $this !== self::PENDING;
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
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
