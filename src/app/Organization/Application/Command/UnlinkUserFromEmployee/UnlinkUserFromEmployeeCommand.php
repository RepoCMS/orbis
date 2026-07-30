<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\UnlinkUserFromEmployee;

final readonly class UnlinkUserFromEmployeeCommand
{
    public function __construct(
        public string $employeeId,
    ) {
    }
}
