<?php

declare(strict_types=1);

namespace App\Organization\Domain\Enums;

enum EmploymentPeriodStatus: string
{
    case ACTIVE = 'active';
    case CLOSED = 'closed';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
