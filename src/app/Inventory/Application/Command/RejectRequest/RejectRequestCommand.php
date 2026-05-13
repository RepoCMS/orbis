<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\RejectRequest;

final readonly class RejectRequestCommand
{
    public function __construct(
        public string $requestId,
        public string $rejectedBy,
        public string $reason,
    ) {
    }
}
