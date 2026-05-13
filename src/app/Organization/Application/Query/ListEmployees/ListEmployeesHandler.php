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
        return array_map(
            static fn($employee) => EmployeeResponse::fromEntity($employee),
            $this->employeeRepository->findAll(),
        );
    }
}
