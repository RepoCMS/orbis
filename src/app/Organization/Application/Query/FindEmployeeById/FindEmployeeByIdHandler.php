<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\FindEmployeeById;

use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Application\Query\Response\EmployeeResponse;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;

final readonly class FindEmployeeByIdHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
    ) {
    }

    public function __invoke(FindEmployeeByIdQuery $query): EmployeeResponse
    {
        $employeeId = new EmployeeIdValueObject($query->employeeId);
        $employee   = $this->employeeRepository->findById($employeeId)
            ?? throw EmployeeNotFoundException::withId($query->employeeId);

        return EmployeeResponse::fromEntity($employee);
    }
}
