<?php

declare(strict_types=1);

namespace App\Organization\Domain\Enums;

enum EmployeeSpecialPeriodStatus: string
{
    /** Programado para el futuro. */
    case SCHEDULED   = 'scheduled';

    /** En curso (startedAt ya pasó). */
    case IN_PROGRESS = 'in_progress';

    /** Finalizado (endedAt ya pasó). */
    case COMPLETED   = 'completed';

    /** Cancelado manualmente antes de completarse. */
    case CANCELLED   = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::SCHEDULED   => 'Programado',
            self::IN_PROGRESS => 'En curso',
            self::COMPLETED   => 'Completado',
            self::CANCELLED   => 'Cancelado',
        };
    }

    public function isActive(): bool
    {
        return $this === self::SCHEDULED || $this === self::IN_PROGRESS;
    }
}
