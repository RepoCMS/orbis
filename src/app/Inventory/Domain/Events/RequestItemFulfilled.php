<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

final class RequestItemFulfilled extends DomainEvent
{
    public function __construct(
        public readonly string $requestId,
        public readonly string $requestReference,
        public readonly string $itemId,
        public readonly string $productId,
        public readonly int $quantity,
        public readonly string $movementId,
    ) {
        parent::__construct();
    }
}
