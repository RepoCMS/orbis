<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\ReactivateEmployee;

use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Entities\EmploymentPeriod;
use App\Organization\Domain\Events\EmployeeReactivated;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\EmploymentPeriodRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Organization\Domain\ValueObjects\EmploymentPeriodIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class ReactivateEmployeeHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private EmploymentPeriodRepositoryInterface $employmentPeriodRepository,
        private IdGeneratorInterface $idGenerator,
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

        // G-07: create new EmploymentPeriod on reactivation (startedAt = now)
        $period = EmploymentPeriod::start(
            new EmploymentPeriodIdValueObject($this->idGenerator->generate()),
            $employeeId,
        );
        $this->employmentPeriodRepository->save($period);

        $this->publisher->publish(new EmployeeReactivated(
            employeeId: $command->employeeId,
            userId:     $employee->userId() !== null ? (string) $employee->userId() : null,
        ));
    }
}
