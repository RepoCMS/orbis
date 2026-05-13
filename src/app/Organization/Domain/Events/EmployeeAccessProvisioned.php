<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando se crea y vincula una cuenta de acceso a un empleado.
 *
 * Listeners típicos:
 *   - Enviar email de bienvenida al sistema.
 *   - Registrar auditoría de alta de acceso.
 */
final class EmployeeAccessProvisioned extends DomainEvent
{
    public function __construct(
        public readonly string $employeeId,
        public readonly string $userId,
    ) {
        parent::__construct();
    }
}
