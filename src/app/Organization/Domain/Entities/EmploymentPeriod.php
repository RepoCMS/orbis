<?php

declare(strict_types=1);

namespace App\Organization\Domain\Entities;

use App\Organization\Domain\Enums\EmploymentPeriodStatus;
use App\Organization\Domain\Exceptions\EmploymentPeriodException;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmploymentPeriodIdValueObject;
use DateTimeImmutable;

/**
 * Registra un período de empleo de un colaborador.
 *
 * Cuando un empleado es contratado, se crea un EmploymentPeriod con status ACTIVE.
 * Cuando es terminado (desactivado), el período se cierra (CLOSED) con endedAt.
 * Cuando es reactivado, se inicia un nuevo EmploymentPeriod.
 *
 * Esto permite trackear el historial completo de altas y bajas del empleado.
 */
final class EmploymentPeriod
{
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $deletedAt = null;

    private function __construct(
        private EmploymentPeriodIdValueObject $id,
        private EmployeeIdValueObject $employeeId,
        private DateTimeImmutable $startedAt,
        private ?DateTimeImmutable $endedAt,
        private EmploymentPeriodStatus $status,
    ) {
        $this->createdAt = new DateTimeImmutable();
    }

    public static function start(
        EmploymentPeriodIdValueObject $id,
        EmployeeIdValueObject $employeeId,
        ?DateTimeImmutable $startedAt = null,
    ): self {
        return new self(
            $id,
            $employeeId,
            $startedAt ?? new DateTimeImmutable(),
            null,
            EmploymentPeriodStatus::ACTIVE,
        );
    }

    public function close(DateTimeImmutable $endedAt): void
    {
        if ($this->status !== EmploymentPeriodStatus::ACTIVE) {
            throw new EmploymentPeriodException('Solo se puede cerrar un período de empleo activo.');
        }

        if ($endedAt < $this->startedAt) {
            throw new EmploymentPeriodException('La fecha de cierre no puede ser anterior a la fecha de inicio.');
        }

        $this->endedAt = $endedAt;
        $this->status = EmploymentPeriodStatus::CLOSED;
    }

    public function id(): EmploymentPeriodIdValueObject
    {
        return $this->id;
    }

    public function employeeId(): EmployeeIdValueObject
    {
        return $this->employeeId;
    }

    public function startedAt(): DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function endedAt(): ?DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function status(): EmploymentPeriodStatus
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }
}
