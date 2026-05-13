<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;

final class EmployeeIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'employee_id';
    }

    protected function getValueObjectClass(): string
    {
        return EmployeeIdValueObject::class;
    }
}
