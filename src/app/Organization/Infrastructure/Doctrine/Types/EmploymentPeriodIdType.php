<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine\Types;

use App\Organization\Domain\ValueObjects\EmploymentPeriodIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class EmploymentPeriodIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'employment_period_id';
    }

    protected function getValueObjectClass(): string
    {
        return EmploymentPeriodIdValueObject::class;
    }
}
