<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\SoftDeleteEmployee;

final readonly class SoftDeleteEmployeeCommand
{
    public function __construct(public string $employeeId)
    {
    }
}