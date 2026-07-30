<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entities;

use App\Organization\Domain\Enums\EmployeeSpecialPeriodStatus;
use App\Organization\Domain\Enums\EmployeeSpecialPeriodType;
use App\Organization\Domain\Exceptions\EmployeeSpecialPeriodException;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeSpecialPeriodIdValueObject;
use DateTimeImmutable;

/**
 * Registra un período especial programado para un empleado.
 *
 * VACATION — El empleado está ausente (vacaciones).
 *   Al inicio del período: estado del empleado → ON_VACATION.
 *   Al fin del período:    estado del empleado → ACTIVE.
 *   Sirve para contextualizar productividad en reportes.
 *
 * STANDBY — El empleado trabaja en fin de semana / día festivo (pago extra).
 *   El estado del empleado NO cambia (permanece ACTIVE).
 *   Sirve para registrar cuántos standby ha tenido, en qué fechas y qué realizó.
 *   A futuro, los tickets atendidos durante un standby se vincularán a este registro.
 *
 * Flujo de estados:
 *   SCHEDULED → IN_PROGRESS (cuando startedAt llega, via ProcessEmployeeSpecialPeriodsCommand)
 *   IN_PROGRESS → COMPLETED  (cuando endedAt llega, via ProcessEmployeeSpecialPeriodsCommand)
 *   SCHEDULED | IN_PROGRESS → CANCELLED (por acción manual)
 */
final class EmployeeSpecialPeriod
{
    private EmployeeSpecialPeriodStatus $status;

    private function __construct(
        private EmployeeSpecialPeriodIdValueObject $id,
        private EmployeeIdValueObject $employeeId,
        private EmployeeSpecialPeriodType $type,
        private DateTimeImmutable $startedAt,
        private DateTimeImmutable $endedAt,
        private ?string $notes,
    ) {
        if ($endedAt <= $startedAt) {
            throw new EmployeeSpecialPeriodException(
                'La fecha de fin debe ser posterior a la fecha de inicio del período.'
            );
        }

        // Si el startedAt ya pasó, el período empieza en IN_PROGRESS directamente.
        $this->status = $startedAt <= new DateTimeImmutable()
            ? EmployeeSpecialPeriodStatus::IN_PROGRESS
            : EmployeeSpecialPeriodStatus::SCHEDULED;
    }

    public static function schedule(
        EmployeeSpecialPeriodIdValueObject $id,
        EmployeeIdValueObject $employeeId,
        EmployeeSpecialPeriodType $type,
        DateTimeImmutable $startedAt,
        DateTimeImmutable $endedAt,
        ?string $notes = null,
    ): self {
        return new self($id, $employeeId, $type, $startedAt, $endedAt, $notes);
    }

    /**
     * El proceso automático llama este método cuando startedAt llega.
     * SCHEDULED → IN_PROGRESS
     */
    public function begin(): void
    {
        if ($this->status !== EmployeeSpecialPeriodStatus::SCHEDULED) {
            throw new EmployeeSpecialPeriodException('Solo se puede iniciar un período programado.');
        }

        $this->status = EmployeeSpecialPeriodStatus::IN_PROGRESS;
    }

    /**
     * El proceso automático llama este método cuando endedAt llega.
     * IN_PROGRESS → COMPLETED
     */
    public function complete(): void
    {
        if ($this->status !== EmployeeSpecialPeriodStatus::IN_PROGRESS) {
            throw new EmployeeSpecialPeriodException('Solo se puede completar un período en curso.');
        }

        $this->status = EmployeeSpecialPeriodStatus::COMPLETED;
    }

    /**
     * Cancela el período manualmente (SCHEDULED o IN_PROGRESS).
     */
    public function cancel(): void
    {
        if (!$this->status->isActive()) {
            throw new EmployeeSpecialPeriodException('No se puede cancelar un período ya completado o cancelado.');
        }

        $this->status = EmployeeSpecialPeriodStatus::CANCELLED;
    }

    public function days(): int
    {
        return (int) $this->endedAt->diff($this->startedAt)->days + 1;
    }

    public function id(): EmployeeSpecialPeriodIdValueObject
    {
        return $this->id;
    }

    public function employeeId(): EmployeeIdValueObject
    {
        return $this->employeeId;
    }

    public function type(): EmployeeSpecialPeriodType
    {
        return $this->type;
    }

    public function status(): EmployeeSpecialPeriodStatus
    {
        return $this->status;
    }

    public function startedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function endedAt(): DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function edit(
        DateTimeImmutable $startedAt,
        DateTimeImmutable $endedAt,
        EmployeeSpecialPeriodType $type,
        ?string $notes = null,
    ): void {
        if ($endedAt <= $startedAt) {
            throw new EmployeeSpecialPeriodException(
                'La fecha de fin debe ser posterior a la fecha de inicio del período.'
            );
        }

        $this->startedAt = $startedAt;
        $this->endedAt   = $endedAt;
        $this->type      = $type;
        $this->notes     = $notes;
    }

    public function completeWithEndDate(?DateTimeImmutable $realEndDate = null): void
    {
        if ($this->status !== EmployeeSpecialPeriodStatus::IN_PROGRESS) {
            throw new EmployeeSpecialPeriodException('Solo se puede completar un período en curso.');
        }

        if ($realEndDate !== null) {
            if ($realEndDate < $this->startedAt) {
                throw new EmployeeSpecialPeriodException(
                    'La fecha de fin real no puede ser anterior a la fecha de inicio.'
                );
            }

            $this->endedAt = $realEndDate;
        }

        $this->status = EmployeeSpecialPeriodStatus::COMPLETED;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }
}
