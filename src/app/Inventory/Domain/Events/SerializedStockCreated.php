<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un nuevo ítem de stock serializado es registrado.
 */
final class SerializedStockCreated extends DomainEvent
{
    public function __construct(
        public readonly string $serializedStockId,
    ) {
        parent::__construct();
    }
}
