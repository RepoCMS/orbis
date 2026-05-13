<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine\Types;

use App\Organization\Domain\Enums\EmployeeSpecialPeriodStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class EmployeeSpecialPeriodStatusEnumType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'employee_special_period_status';
    }

    protected function getEnumClass(): string
    {
        return EmployeeSpecialPeriodStatus::class;
    }
}
