<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\RejectVacationRequest;

final readonly class RejectVacationRequestCommand
{
    public function __construct(
        public string $requestId,
        public string $reviewedBy,
        public string $reason,
    ) {
    }
}
