<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un período especial (vacación/standby) es programado para un empleado.
 */
final class SpecialPeriodScheduled extends DomainEvent
{
    public function __construct(
        public readonly string $periodId,
        public readonly string $employeeId,
        public readonly string $type,
    ) {
        parent::__construct();
    }
}
