<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando una orden de compra es creada.
 */
final class PurchaseOrderCreated extends DomainEvent
{
    public function __construct(
        public readonly string $purchaseOrderId,
    ) {
        parent::__construct();
    }
}
