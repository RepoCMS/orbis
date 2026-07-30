<?php

declare(strict_types=1);

namespace App\Identity\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un Employee es registrado y vinculado a un User.
 *
 * Listeners típicos:
 *   - Notificar al empleado sobre su alta en el sistema.
 *   - Registrar en el log de auditoría.
 */
final class EmployeeRegistered extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $employeeId,
        public readonly string $username,
    ) {
        parent::__construct();
    }
}
