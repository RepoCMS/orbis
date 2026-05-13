<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListEmployeeSpecialPeriods;

use App\Organization\Application\Query\Response\EmployeeSpecialPeriodResponse;
use App\Organization\Domain\Repositories\EmployeeSpecialPeriodRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;

final readonly class ListEmployeeSpecialPeriodsHandler
{
    public function __construct(
        private EmployeeSpecialPeriodRepositoryInterface $periodRepository,
    ) {
    }

    /** @return EmployeeSpecialPeriodResponse[] */
    public function __invoke(ListEmployeeSpecialPeriodsQuery $query): array
    {
        $periods = $this->periodRepository->findAllByEmployee(
            new EmployeeIdValueObject($query->employeeId),
        );

        return array_map(
            static fn ($p) => EmployeeSpecialPeriodResponse::fromEntity($p),
            $periods,
        );
    }
}
