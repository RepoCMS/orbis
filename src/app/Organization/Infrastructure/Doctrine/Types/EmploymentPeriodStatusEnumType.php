<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine\Types;

use App\Organization\Domain\Enums\EmploymentPeriodStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class EmploymentPeriodStatusEnumType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'employment_period_status';
    }

    protected function getEnumClass(): string
    {
        return EmploymentPeriodStatus::class;
    }
}
