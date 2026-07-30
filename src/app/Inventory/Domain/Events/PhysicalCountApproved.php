<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un conteo físico es aprobado y se generan los ajustes.
 */
final class PhysicalCountApproved extends DomainEvent
{
    public function __construct(
        public readonly string $countId,
        public readonly string $approvedBy,
        public readonly int $adjustedItems,
    ) {
        parent::__construct();
    }
}
