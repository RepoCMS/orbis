<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un nuevo producto es creado en el catálogo.
 */
final class ProductCreated extends DomainEvent
{
    public function __construct(
        public readonly string $productId,
        public readonly string $productCode,
    ) {
        parent::__construct();
    }
}
