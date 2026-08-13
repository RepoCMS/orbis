<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando el proceso automático de special periods
 * procesa transiciones (started/completed) de un período específico.
 */
final class SpecialPeriodAutoProcessed extends DomainEvent
{
    public function __construct(
        public readonly string $periodId,
        public readonly string $employeeId,
        public readonly string $type,
        public readonly string $transition, // 'started' | 'completed'
    ) {
        parent::__construct();
    }
}
