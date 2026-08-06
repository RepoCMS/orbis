<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CreateVacationRequest;

final readonly class CreateVacationRequestCommand
{
    public function __construct(
        public string $employeeId,
        public string $startDate,
        public string $endDate,
    ) {
    }
}
