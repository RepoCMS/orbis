<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\UnlinkUserFromEmployee;

use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Events\EmployeeUserUnlinked;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

final readonly class UnlinkUserFromEmployeeHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private UserRepositoryInterface $userRepository,
        private DomainEventPublisherInterface $publisher,
    ) {
    }

    public function __invoke(UnlinkUserFromEmployeeCommand $command): void
    {
        $employeeId = new EmployeeIdValueObject($command->employeeId);
        $employee = $this->employeeRepository->findById($employeeId)
            ?? throw EmployeeNotFoundException::withId($command->employeeId);

        $userId = $employee->userId() !== null ? (string) $employee->userId() : null;

        if ($userId === null) {
            return;
        }

        $user = $this->userRepository->findById(new UserIdValueObject($userId));

        $employee->unlinkUser();

        if ($user !== null) {
            $user->unlinkEmployee();
            $this->userRepository->save($user);
        }

        $this->employeeRepository->save($employee);

        $this->publisher->publish(new EmployeeUserUnlinked(
            employeeId: $command->employeeId,
            userId: $userId,
        ));
    }
}
