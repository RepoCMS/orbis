<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando una solicitud de stock pasa a AWAITING_TECHNICIAN_ACCEPTANCE.
 *
 * Listeners típicos:
 *   - Notificar al técnico por email y/o WhatsApp para que acepte o rechace.
 */
final class RequestAwaitingTechnicianAcceptance extends DomainEvent
{
    public function __construct(
        public readonly string $requestId,
        public readonly string $reference,
        public readonly string $requestedByUserId,
        public readonly string $approvedByUserId,
    ) {
        parent::__construct();
    }
}
