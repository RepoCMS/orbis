<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un empleado es contratado (Employee::hire).
 *
 * Listeners típicos:
 *   - Notificar a RRHH por email.
 *   - Crear registro de onboarding.
 */
final class EmployeeCreated extends DomainEvent
{
    public function __construct(
        public readonly string $employeeId,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
    ) {
        parent::__construct();
    }
}
