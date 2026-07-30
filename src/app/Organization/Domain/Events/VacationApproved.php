<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un supervisor aprueba una solicitud de vacaciones
 * y se crea el EmployeeSpecialPeriod correspondiente.
 */
final class VacationApproved extends DomainEvent
{
    public function __construct(
        public readonly string $employeeId,
        public readonly string $requestId,
        public readonly string $startDate,
        public readonly string $endDate,
    ) {
        parent::__construct();
    }
}
