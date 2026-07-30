<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\DeactivateEmployee;

use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Application\Exception\TerminationBlockedException;
use App\Organization\Application\Service\TerminationVerificationServiceInterface;
use App\Organization\Domain\Events\EmployeeDeactivated;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\Repositories\EmploymentPeriodRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class DeactivateEmployeeHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private EmploymentPeriodRepositoryInterface $employmentPeriodRepository,
        private UserRepositoryInterface $userRepository,
        private DomainEventPublisherInterface $publisher,
        private TerminationVerificationServiceInterface $terminationVerification,
    ) {
    }

    public function __invoke(DeactivateEmployeeCommand $command): void
    {
        $employeeId = new EmployeeIdValueObject($command->employeeId);
        $employee = $this->employeeRepository->findById($employeeId)
            ?? throw EmployeeNotFoundException::withId($command->employeeId);

        $pendingItems = $this->terminationVerification->findBlockingItems($employeeId);
        if (!empty($pendingItems)) {
            throw TerminationBlockedException::withPendingItems($pendingItems);
        }

        $userId = $employee->userId() !== null ? (string) $employee->userId() : null;

        if ($userId !== null) {
            $user = $this->userRepository->findById(new UserIdValueObject($userId));
            $employee->unlinkUser();
            if ($user !== null) {
                $user->unlinkEmployee();
                $this->userRepository->save($user);
            }
        }

        $employee->terminate();

        $this->employeeRepository->save($employee);

        $activePeriod = $this->employmentPeriodRepository->findActiveByEmployee($employeeId);
        if ($activePeriod !== null) {
            $activePeriod->close(new \DateTimeImmutable());
            $this->employmentPeriodRepository->save($activePeriod);
        }

        $this->publisher->publish(new EmployeeDeactivated(
            employeeId: $command->employeeId,
            userId:     $userId,
        ));
    }
}
