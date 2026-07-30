<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando una ciudad es transferida de una zona de servicio a otra.
 */
final class CityTransferred extends DomainEvent
{
    public function __construct(
        public readonly string $cityId,
        public readonly string $fromZoneId,
        public readonly string $toZoneId,
    ) {
        parent::__construct();
    }
}
