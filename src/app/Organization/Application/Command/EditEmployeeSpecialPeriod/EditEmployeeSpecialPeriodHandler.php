<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\EditEmployeeSpecialPeriod;

use App\Organization\Domain\Enums\EmployeeSpecialPeriodType;
use App\Organization\Domain\Exceptions\EmployeeSpecialPeriodException;
use App\Organization\Domain\Repositories\EmployeeSpecialPeriodRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeSpecialPeriodIdValueObject;
use DateTimeImmutable;

final readonly class EditEmployeeSpecialPeriodHandler
{
    public function __construct(
        private EmployeeSpecialPeriodRepositoryInterface $periodRepository,
    ) {
    }

    public function __invoke(EditEmployeeSpecialPeriodCommand $command): void
    {
        $periodId = new EmployeeSpecialPeriodIdValueObject($command->periodId);
        $period   = $this->periodRepository->findById($periodId)
            ?? throw new EmployeeSpecialPeriodException('Período especial no encontrado.');

        if ((string) $period->employeeId() !== $command->employeeId) {
            throw new EmployeeSpecialPeriodException(
                'El período no pertenece al empleado especificado.'
            );
        }

        $type = EmployeeSpecialPeriodType::from($command->type);

        $period->edit(
            startedAt: new DateTimeImmutable($command->startedAt),
            endedAt:   new DateTimeImmutable($command->endedAt),
            type:      $type,
            notes:     $command->notes,
        );

        $this->periodRepository->save($period);
    }
}
