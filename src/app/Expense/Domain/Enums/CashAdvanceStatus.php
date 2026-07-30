<?php

declare(strict_types=1);

namespace App\Expense\Domain\Enums;

/**
 * Status de un adelanto de efectivo (CashAdvance).
 *
 * @see \App\Expense\Domain\Entities\CashAdvance
 */
enum CashAdvanceStatus: string
{
    case OUTSTANDING = 'outstanding';
    case SETTLED = 'settled';
    case PENDING_REVIEW = 'pending_review';

    public function label(): string
    {
        return match ($this) {
            self::OUTSTANDING => 'Pendiente de liquidar',
            self::SETTLED => 'Liquidado',
            self::PENDING_REVIEW => 'Revisión pendiente',
        };
    }

    public function canSettle(): bool
    {
        return $this === self::OUTSTANDING;
    }

    public function isOutstanding(): bool
    {
        return $this === self::OUTSTANDING;
    }

    public function isSettled(): bool
    {
        return $this === self::SETTLED;
    }

    public function isPendingReview(): bool
    {
        return $this === self::PENDING_REVIEW;
    }
}
