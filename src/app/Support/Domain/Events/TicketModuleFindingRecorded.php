<?php

declare(strict_types=1);

namespace App\Support\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Domain Event emitido cuando se registra un hallazgo de módulo de ATM en un ticket.
 *
 * Ocurre cuando un técnico reporta la revisión de un componente específico
 * del árbol de módulos del ATM, indicando si está OK, tiene falla, fue
 * reemplazado o está pendiente.
 */
final class TicketModuleFindingRecorded extends DomainEvent
{
    public function __construct(
        public readonly string $findingId,
        public readonly string $ticketId,
        public readonly string $modulePath,
        public readonly string $componentName,
        public readonly string $findingType,
        public readonly string $registeredBy,
    ) {
        parent::__construct();
    }
}
