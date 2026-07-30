<?php

declare(strict_types=1);

namespace App\Support\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se emite cuando un ticket ha superado su SLA deadline.
 * Dispara notificaciones al supervisor y administrador.
 */
final class SlaBreached extends DomainEvent
{
    /** @param string[] $assignedEmployeeIds */
    public function __construct(
        public readonly string $ticketId,
        public readonly string $ticketNumber,
        public readonly \DateTimeImmutable $slaDeadlineAt,
        public readonly string $inChargeEmployeeId,
        public readonly array $assignedEmployeeIds,
    ) {
        parent::__construct();
    }
}
