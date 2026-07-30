<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando el stock de un producto en una ubicación cae por debajo del umbral mínimo.
 */
final class StockBelowThreshold extends DomainEvent
{
    public function __construct(
        public readonly string $productId,
        public readonly string $locationId,
    ) {
        parent::__construct();
    }
}
