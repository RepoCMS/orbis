<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un empleado cancela su solicitud de vacaciones
 * antes de que sea aprobada.
 */
final class VacationCancelled extends DomainEvent
{
    public function __construct(
        public readonly string $employeeId,
        public readonly string $requestId,
    ) {
        parent::__construct();
    }
}
