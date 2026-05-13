<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine\Types;

use App\Organization\Domain\Enums\EmployeeStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class EmployeeStatusType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'employee_status';
    }

    protected function getEnumClass(): string
    {
        return EmployeeStatus::class;
    }
}
