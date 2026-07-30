<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando se registra un cambio en el historial de números de serie.
 */
final class SerialHistoryRecorded extends DomainEvent
{
    public function __construct(
        public readonly string $serializedStockId,
        public readonly string $reason,
    ) {
        parent::__construct();
    }
}
