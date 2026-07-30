<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un repuesto es usado en una emergencia de campo,
 * moviendo el ítem a estado SUSPECT.
 */
final class EmergencyPartUsed extends DomainEvent
{
    public function __construct(
        public readonly string $productId,
        public readonly string $serializedStockId,
        public readonly string $employeeId,
        public readonly ?string $notes,
    ) {
        parent::__construct();
    }
}
