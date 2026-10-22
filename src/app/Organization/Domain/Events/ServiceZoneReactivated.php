<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando una zona de servicio es reactivada.
 */
final class ServiceZoneReactivated extends DomainEvent
{
    public function __construct(
        public readonly string $zoneId,
        public readonly string $createdBy,
    ) {
        parent::__construct();
    }
}
