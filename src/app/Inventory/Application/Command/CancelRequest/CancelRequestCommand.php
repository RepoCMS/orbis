<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\CancelRequest;

final readonly class CancelRequestCommand
{
    public function __construct(
        public string $requestId,
        public string $cancelledBy,
    ) {
    }
}
