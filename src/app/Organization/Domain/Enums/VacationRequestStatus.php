<?php

declare(strict_types=1);

namespace App\Organization\Domain\Enums;

enum VacationRequestStatus: string
{
    /** Pendiente de aprobación por el supervisor. */
    case PENDING = 'pending';

    /** Aprobado por el supervisor — se creará el EmployeeSpecialPeriod. */
    case APPROVED = 'approved';

    /** Rechazado por el supervisor. */
    case REJECTED = 'rejected';

    /** Cancelado por el empleado antes de ser aprobado. */
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING   => 'Pendiente',
            self::APPROVED  => 'Aprobado',
            self::REJECTED  => 'Rechazado',
            self::CANCELLED => 'Cancelado',
        };
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }
}
