<?php

declare(strict_types=1);

namespace App\Organization\Domain\Events;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Se publica cuando un empleado es eliminado lógicamente (softDelete).
 *
 * Listeners típicos:
 *   - Soft-delete del User vinculado (Identity BC).
 */
final class EmployeeDeleted extends DomainEvent
{
    public function __construct(
        public readonly string $employeeId,
        public readonly ?string $userId,
    ) {
        parent::__construct();
    }
}
