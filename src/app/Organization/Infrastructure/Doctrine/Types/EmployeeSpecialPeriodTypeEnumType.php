<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine\Types;

use App\Organization\Domain\Enums\EmployeeSpecialPeriodType;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class EmployeeSpecialPeriodTypeEnumType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'employee_special_period_type';
    }

    protected function getEnumClass(): string
    {
        return EmployeeSpecialPeriodType::class;
    }
}
