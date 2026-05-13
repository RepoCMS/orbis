<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\DeactivateEmployee;

final readonly class DeactivateEmployeeCommand
{
    public function __construct(public string $employeeId)
    {
    }
}