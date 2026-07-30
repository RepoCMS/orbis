<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando se inicia un nuevo conteo físico.
 */
final class PhysicalCountStarted extends DomainEvent
{
    public function __construct(
        public readonly string $countId,
        public readonly string $locationId,
        public readonly string $countedBy,
    ) {
        parent::__construct();
    }
}
