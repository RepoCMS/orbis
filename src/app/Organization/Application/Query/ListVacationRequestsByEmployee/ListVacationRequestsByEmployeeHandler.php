<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListVacationRequestsByEmployee;

use App\Organization\Application\Query\Response\VacationRequestResponse;
use App\Organization\Domain\Repositories\VacationRequestRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;

final readonly class ListVacationRequestsByEmployeeHandler
{
    public function __construct(
        private VacationRequestRepositoryInterface $vacationRequestRepository,
    ) {
    }

    /** @return VacationRequestResponse[] */
    public function __invoke(ListVacationRequestsByEmployeeQuery $query): array
    {
        $requests = $this->vacationRequestRepository->findAllByEmployee(
            new EmployeeIdValueObject($query->employeeId),
        );

        return array_map(
            static fn ($r) => VacationRequestResponse::fromEntity($r),
            $requests,
        );
    }
}
