<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine\Types;

use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class EmployeeIdentificationNumberType extends AbstractStringValueObjectType
{
    public function getName(): string
    {
        return 'employee_identification_number';
    }

    protected function getValueObjectClass(): string
    {
        return EmployeeIdentificationNumberValueObject::class;
    }
}
