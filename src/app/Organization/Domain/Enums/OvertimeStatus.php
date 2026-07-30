<?php

declare(strict_types=1);

namespace App\Organization\Domain\Enums;

enum OvertimeStatus: string
{
    case PENDING = 'pending';
    case IN_REVIEW = 'in_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING   => 'Pendiente',
            self::IN_REVIEW => 'En revisión',
            self::APPROVED  => 'Aprobado',
            self::REJECTED  => 'Rechazado',
        };
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isInReview(): bool
    {
        return $this === self::IN_REVIEW;
    }
}
