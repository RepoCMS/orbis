<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\ListEmployees;

use App\Organization\Application\Query\Response\EmployeeResponse;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;

final readonly class ListEmployeesHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
    ) {
    }

    public function __invoke(ListEmployeesQuery $query): ListEmployeesResult
    {
        $employees = $this->employeeRepository->findByFilters(
            profile: $query->profile,
            status: $query->status,
            search: $query->search,
            serviceZoneId: $query->serviceZoneId,
            page: $query->page,
            limit: $query->limit,
        );

        $total = $this->employeeRepository->countByFilters(
            profile: $query->profile,
            status: $query->status,
            search: $query->search,
            serviceZoneId: $query->serviceZoneId,
        );

        return new ListEmployeesResult(
            items: array_map(
                static fn ($employee) => EmployeeResponse::fromEntity($employee),
                $employees,
            ),
            total: $total,
            page: $query->page,
            limit: $query->limit,
        );
    }
}
