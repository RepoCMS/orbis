<?php

declare(strict_types=1);

namespace App\Organization\Domain\Repositories;

use App\Organization\Domain\Entities\EmployeeSpecialPeriod;
use App\Organization\Domain\Enums\EmployeeSpecialPeriodType;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeSpecialPeriodIdValueObject;
use DateTimeImmutable;

interface EmployeeSpecialPeriodRepositoryInterface
{
    public function save(EmployeeSpecialPeriod $period): void;

    public function findById(EmployeeSpecialPeriodIdValueObject $id): ?EmployeeSpecialPeriod;

    /**
     * Retorna el período activo (SCHEDULED o IN_PROGRESS) de un tipo dado para un empleado,
     * o null si no hay ninguno activo.
     */
    public function findActiveByEmployeeAndType(
        EmployeeIdValueObject $employeeId,
        EmployeeSpecialPeriodType $type,
    ): ?EmployeeSpecialPeriod;

    /**
     * Períodos en estado SCHEDULED cuyo startedAt ya llegó (≤ now).
     * El proceso automático los transiciona a IN_PROGRESS.
     *
     * @return EmployeeSpecialPeriod[]
     */
    public function findDueToStart(): array;

    /**
     * Períodos en estado IN_PROGRESS cuyo endedAt ya llegó (≤ now).
     * El proceso automático los transiciona a COMPLETED.
     *
     * @return EmployeeSpecialPeriod[]
     */
    public function findDueToEnd(): array;

    /**
     * Períodos activos (SCHEDULED o IN_PROGRESS) de cualquier tipo que se solapen
     * con el rango [startedAt, endedAt] para el empleado dado.
     * Dos intervalos se solapan cuando: startedAt1 < endedAt2 AND endedAt1 > startedAt2
     *
     * @return EmployeeSpecialPeriod[]
     */
    public function findOverlappingByEmployee(
        EmployeeIdValueObject $employeeId,
        DateTimeImmutable $startedAt,
        DateTimeImmutable $endedAt,
    ): array;

    /**
     * Historial completo de períodos especiales de un empleado, más recientes primero.
     *
     * @return EmployeeSpecialPeriod[]
     */
    public function findAllByEmployee(EmployeeIdValueObject $employeeId): array;
}
