<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando una pieza serializada completa su reparación.
 */
final class PartRepaired extends DomainEvent
{
    public function __construct(
        public readonly string $serializedStockId,
    ) {
        parent::__construct();
    }
}
