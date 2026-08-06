<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\ReactivateEmployee;

use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Events\EmployeeReactivated;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class ReactivateEmployeeHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private DomainEventPublisherInterface $publisher,
    ) {
    }

    public function __invoke(ReactivateEmployeeCommand $command): void
    {
        $employeeId = new EmployeeIdValueObject($command->employeeId);
        $employee = $this->employeeRepository->findById($employeeId)
            ?? throw EmployeeNotFoundException::withId($command->employeeId);

        $employee->reactivate();

        $this->employeeRepository->save($employee);

        $this->publisher->publish(new EmployeeReactivated(
            employeeId: $command->employeeId,
            userId:     $employee->userId() !== null ? (string) $employee->userId() : null,
        ));
    }
}
