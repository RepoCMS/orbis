<?php

declare(strict_types=1);

namespace App\Inventory\Application\Command\FulfillRequestItem;

final readonly class FulfillRequestItemCommand
{
    public function __construct(
        public string $requestId,
        public string $itemId,
    ) {
    }
}
