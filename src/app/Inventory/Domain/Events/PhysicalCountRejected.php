<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un conteo físico es rechazado.
 */
final class PhysicalCountRejected extends DomainEvent
{
    public function __construct(
        public readonly string $countId,
        public readonly string $reason,
    ) {
        parent::__construct();
    }
}
