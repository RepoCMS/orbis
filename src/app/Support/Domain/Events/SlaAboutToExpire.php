<?php

declare(strict_types=1);

namespace App\Support\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se emite cuando un ticket tiene su SLA próximo a vencer (menos de 1 hora).
 * Dispara notificaciones al técnico asignado y al supervisor.
 */
final class SlaAboutToExpire extends DomainEvent
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
