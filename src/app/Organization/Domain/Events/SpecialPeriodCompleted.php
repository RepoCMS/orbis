<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un período especial es completado automáticamente
 * (el cron detectó que endedAt llegó).
 */
final class SpecialPeriodCompleted extends DomainEvent
{
    public function __construct(
        public readonly string $periodId,
        public readonly string $employeeId,
    ) {
        parent::__construct();
    }
}
