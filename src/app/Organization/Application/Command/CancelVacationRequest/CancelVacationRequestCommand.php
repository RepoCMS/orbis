<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CancelVacationRequest;

final readonly class CancelVacationRequestCommand
{
    public function __construct(
        public string $requestId,
        public string $employeeId,
    ) {
    }
}
