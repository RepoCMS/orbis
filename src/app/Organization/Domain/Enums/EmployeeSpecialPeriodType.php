<?php

declare(strict_types=1);

namespace App\Organization\Domain\Enums;

enum EmployeeSpecialPeriodType: string
{
    /**
     * Período de vacaciones: el empleado está ausente.
     * Sirve para explicar baja productividad en reportes.
     */
    case VACATION = 'vacation';

    /**
     * Período de standby: el empleado trabaja un fin de semana o día festivo.
     * Se remunera como tiempo extra. Sirve para vincular tareas/tickets
     * atendidos fuera del horario normal.
     */
    case STANDBY = 'standby';

    public function label(): string
    {
        return match($this) {
            self::VACATION => 'Vacaciones',
            self::STANDBY  => 'Standby',
        };
    }
}
