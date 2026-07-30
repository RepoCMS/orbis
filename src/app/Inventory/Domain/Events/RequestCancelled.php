<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando una solicitud de stock es cancelada.
 *
 * Contiene datos para notificar al responsable de la ubicación origen
 * o al técnico solicitante.
 */
final class RequestCancelled extends DomainEvent
{
    public function __construct(
        public readonly string $requestId,
        public readonly string $reference,
        public readonly string $requestedByUserId,
        public readonly string $sourceLocationId,
        public readonly string $cancelledByUserId,
    ) {
        parent::__construct();
    }
}
