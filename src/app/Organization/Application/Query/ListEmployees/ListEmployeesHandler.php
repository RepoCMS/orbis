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

    /**
     * @return EmployeeResponse[]
     */
    public function __invoke(ListEmployeesQuery $query): array
    {
        $employees = $query->profile !== null || $query->status !== null || $query->search !== null
            ? $this->employeeRepository->findByFilters(
                profile: $query->profile,
                status: $query->status,
                search: $query->search,
            )
            : $this->employeeRepository->findAll();

        return array_map(
            static fn ($employee) => EmployeeResponse::fromEntity($employee),
            $employees,
        );
    }
}
