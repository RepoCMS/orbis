<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un movimiento de stock es completado.
 */
final class StockMoved extends DomainEvent
{
    public function __construct(
        public readonly string $movementId,
    ) {
        parent::__construct();
    }
}
