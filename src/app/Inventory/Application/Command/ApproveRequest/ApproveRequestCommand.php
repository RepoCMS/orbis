<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\ApproveRequest;

final readonly class ApproveRequestCommand
{
    public function __construct(
        public string $requestId,
        public string $approvedBy,
    ) {
    }
}
