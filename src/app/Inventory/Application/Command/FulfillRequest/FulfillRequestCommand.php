<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\FulfillRequest;

final readonly class FulfillRequestCommand
{
    public function __construct(
        public string $requestId,
    ) {
    }
}
