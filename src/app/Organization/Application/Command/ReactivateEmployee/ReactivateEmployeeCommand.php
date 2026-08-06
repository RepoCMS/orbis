<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\ReactivateEmployee;

final readonly class ReactivateEmployeeCommand
{
    public function __construct(public string $employeeId)
    {
    }
}
