<?php

declare(strict_types=1);

namespace App\Organization\Infrastructure\Doctrine\Types;

use App\Organization\Domain\Enums\EmployeeProfile;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class EmployeeProfileEnumType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'employee_profile';
    }

    protected function getEnumClass(): string
    {
        return EmployeeProfile::class;
    }
}
