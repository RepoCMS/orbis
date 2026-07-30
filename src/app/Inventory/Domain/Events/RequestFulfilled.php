<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando una solicitud de stock es satisfecha (entregada).
 *
 * Contiene datos para notificar al técnico solicitante.
 */
final class RequestFulfilled extends DomainEvent
{
    public function __construct(
        public readonly string $requestId,
        public readonly string $reference,
        public readonly string $requestedByUserId,
    ) {
        parent::__construct();
    }
}
