<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando una entrada de inventario es confirmada.
 */
final class EntryConfirmed extends DomainEvent
{
    public function __construct(
        public readonly string $entryId,
        public readonly string $employeeId,
    ) {
        parent::__construct();
    }
}
