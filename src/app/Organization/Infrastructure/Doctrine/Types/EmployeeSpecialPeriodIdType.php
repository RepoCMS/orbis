<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine\Types;

use App\Organization\Domain\ValueObjects\EmployeeSpecialPeriodIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class EmployeeSpecialPeriodIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'employee_special_period_id';
    }

    protected function getValueObjectClass(): string
    {
        return EmployeeSpecialPeriodIdValueObject::class;
    }
}
