<?php

namespace App\Organization\Domain\Enums;

enum EmployeeStatus: string
{
    case ACTIVE      = 'active';
    case INACTIVE    = 'inactive';
    case ON_VACATION = 'on_vacation';

    /** Estados en que el empleado sigue contratado (sin terminatedAt). */
    public function isEmployed(): bool
    {
        return $this !== self::INACTIVE;
    }

    /**
     * El empleado está disponible para recibir asignación de tickets.
     * ON_VACATION = ausente, por tanto no disponible.
     * El standby (trabajo en fin de semana) no cambia este estado; el
     * empleado sigue ACTIVE y puede recibir tareas.
     */
    public function isAvailableForAssignment(): bool
    {
        return $this === self::ACTIVE;
    }
}