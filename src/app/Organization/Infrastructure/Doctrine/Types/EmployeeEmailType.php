<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine\Types;

use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class EmployeeEmailType extends AbstractStringValueObjectType
{
    public function getName(): string
    {
        return 'employee_email';
    }

    protected function getValueObjectClass(): string
    {
        return EmployeeEmailValueObject::class;
    }
}
