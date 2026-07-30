<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un repuesto serializado es transformado en laboratorio.
 */
final class PartTransformed extends DomainEvent
{
    public function __construct(
        public readonly string $transformationId,
    ) {
        parent::__construct();
    }
}
