<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un empleado solicita vacaciones (se crea VacationRequest en PENDING).
 */
final class VacationRequested extends DomainEvent
{
    public function __construct(
        public readonly string $employeeId,
        public readonly string $startDate,
        public readonly string $endDate,
    ) {
        parent::__construct();
    }
}
