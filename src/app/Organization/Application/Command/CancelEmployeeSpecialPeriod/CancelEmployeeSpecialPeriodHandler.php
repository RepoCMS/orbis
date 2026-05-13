<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CancelEmployeeSpecialPeriod;

use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Enums\EmployeeSpecialPeriodStatus;
use App\Organization\Domain\Enums\EmployeeSpecialPeriodType;
use App\Organization\Domain\Exceptions\EmployeeSpecialPeriodException;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\EmployeeSpecialPeriodRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeSpecialPeriodIdValueObject;

final readonly class CancelEmployeeSpecialPeriodHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private EmployeeSpecialPeriodRepositoryInterface $periodRepository,
    ) {
    }

    public function __invoke(CancelEmployeeSpecialPeriodCommand $command): void
    {
        $periodId = new EmployeeSpecialPeriodIdValueObject($command->periodId);
        $period   = $this->periodRepository->findById($periodId)
            ?? throw new EmployeeSpecialPeriodException('Período especial no encontrado.');

        $wasInProgress = $period->status() === EmployeeSpecialPeriodStatus::IN_PROGRESS;

        $period->cancel();
        $this->periodRepository->save($period);

        // Si el período estaba en curso y era una VACATION, revertir el estado del empleado
        if ($wasInProgress && $period->type() === EmployeeSpecialPeriodType::VACATION) {
            $employeeId = new EmployeeIdValueObject($command->employeeId);
            $employee   = $this->employeeRepository->findById($employeeId)
                ?? throw EmployeeNotFoundException::withId($command->employeeId);

            $employee->returnFromVacation();
            $this->employeeRepository->save($employee);
        }
    }
}
