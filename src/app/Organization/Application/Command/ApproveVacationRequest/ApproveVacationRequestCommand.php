<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\ApproveVacationRequest;

final readonly class ApproveVacationRequestCommand
{
    public function __construct(
        public string $requestId,
        public string $reviewedBy,
    ) {
    }
}
