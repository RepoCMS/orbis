<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando una nueva zona de servicio es creada.
 */
final class ServiceZoneCreated extends DomainEvent
{
    public function __construct(
        public readonly string $zoneId,
        public readonly string $name,
    ) {
        parent::__construct();
    }
}
