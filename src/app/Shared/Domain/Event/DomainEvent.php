<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use DateTimeImmutable;

/**
 * Base de todos los Domain Events del sistema.
 *
 * Un Domain Event representa algo que ocurrió en el dominio y que puede
 * ser de interés para otros Bounded Contexts o para lógica secundaria
 * (notificaciones, auditoría, efectos secundarios).
 *
 * Naming convention: pasado del verbo — UserRegistered, EmployeeCreated, TicketAssigned.
 * Son inmutables — no tienen setters.
 */
abstract class DomainEvent
{
    private readonly DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->occurredAt = new DateTimeImmutable();
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
