<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\ScheduleEmployeeStandby;

use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Entities\EmployeeSpecialPeriod;
use App\Organization\Domain\Enums\EmployeeSpecialPeriodStatus;
use App\Organization\Domain\Enums\EmployeeSpecialPeriodType;
use App\Organization\Domain\Exceptions\EmployeeSpecialPeriodException;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\EmployeeSpecialPeriodRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeSpecialPeriodIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use DateTimeImmutable;

final readonly class ScheduleEmployeeStandbyHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private EmployeeSpecialPeriodRepositoryInterface $periodRepository,
        private IdGeneratorInterface $idGenerator,
    ) {}

    public function __invoke(ScheduleEmployeeStandbyCommand $command): void
    {
        $employeeId = new EmployeeIdValueObject($command->employeeId);
        $employee   = $this->employeeRepository->findById($employeeId)
            ?? throw EmployeeNotFoundException::withId($command->employeeId);

        // El standby NO cambia el estado del empleado — solo verifica que esté contratado
        if (!$employee->status()->isEmployed()) {
            throw new EmployeeSpecialPeriodException(
                'No se puede programar un standby para un empleado inactivo.'
            );
        }

        $startedAt = new DateTimeImmutable($command->startedAt);
        $endedAt   = new DateTimeImmutable($command->endedAt);

        // Validar que no exista ningún período (de cualquier tipo) que se solape con las fechas
        $overlapping = $this->periodRepository->findOverlappingByEmployee($employeeId, $startedAt, $endedAt);
        if (count($overlapping) > 0) {
            throw new EmployeeSpecialPeriodException(
                'El empleado ya tiene un período activo que se solapa con las fechas indicadas.'
            );
        }

        $period = EmployeeSpecialPeriod::schedule(
            id: new EmployeeSpecialPeriodIdValueObject($this->idGenerator->generate()),
            employeeId: $employeeId,
            type: EmployeeSpecialPeriodType::STANDBY,
            startedAt: $startedAt,
            endedAt: $endedAt,
            notes: $command->notes,
        );

        $this->periodRepository->save($period);
        // No se modifica el estado del empleado — permanece ACTIVE durante el standby.
    }
}
