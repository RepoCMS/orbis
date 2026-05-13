<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un empleado es reactivado.
 *
 * Listeners típicos:
 *   - Reactivar el User vinculado si estaba INACTIVE (Identity BC).
 */
final class EmployeeReactivated extends DomainEvent
{
    public function __construct(
        public readonly string $employeeId,
        public readonly ?string $userId,
    ) {
        parent::__construct();
    }
}
