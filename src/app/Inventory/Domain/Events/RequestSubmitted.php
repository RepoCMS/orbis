<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un técnico crea una solicitud de stock (repuesto).
 *
 * Contiene los datos necesarios para notificar al responsable de la
 * ubicación origen y al técnico solicitante.
 */
final class RequestSubmitted extends DomainEvent
{
    public function __construct(
        public readonly string $requestId,
        public readonly string $reference,
        public readonly string $requestedByUserId,
        public readonly string $productId,
        public readonly int $quantity,
        public readonly string $sourceLocationId,
        public readonly ?string $sourceResponsibleUserId = null,
        public readonly ?string $notes = null,
    ) {
        parent::__construct();
    }
}
