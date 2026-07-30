<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\CompleteEmployeeSpecialPeriod;

use App\Organization\Domain\Enums\EmployeeSpecialPeriodType;
use App\Organization\Domain\Exceptions\EmployeeSpecialPeriodException;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\EmployeeSpecialPeriodRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmployeeSpecialPeriodIdValueObject;
use DateTimeImmutable;

final readonly class CompleteEmployeeSpecialPeriodHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private EmployeeSpecialPeriodRepositoryInterface $periodRepository,
    ) {
    }

    public function __invoke(CompleteEmployeeSpecialPeriodCommand $command): void
    {
        $periodId = new EmployeeSpecialPeriodIdValueObject($command->periodId);
        $period   = $this->periodRepository->findById($periodId)
            ?? throw new EmployeeSpecialPeriodException('Período especial no encontrado.');

        if ((string) $period->employeeId() !== $command->employeeId) {
            throw new EmployeeSpecialPeriodException(
                'El período no pertenece al empleado especificado.'
            );
        }

        $realEndDate = $command->realEndDate !== null
            ? new DateTimeImmutable($command->realEndDate)
            : null;

        $period->completeWithEndDate($realEndDate);
        $this->periodRepository->save($period);

        // Si era una VACATION en curso, restaurar estado del empleado
        if ($period->type() === EmployeeSpecialPeriodType::VACATION) {
            $employeeId = new EmployeeIdValueObject($command->employeeId);
            $employee   = $this->employeeRepository->findById($employeeId);

            if ($employee !== null && $employee->status()->isEmployed()) {
                $employee->returnFromVacation();
                $this->employeeRepository->save($employee);
            }
        }
    }
}
