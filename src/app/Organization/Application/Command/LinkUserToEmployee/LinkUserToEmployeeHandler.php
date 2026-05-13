<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\LinkUserToEmployee;

use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Service\UserBindingCheckerInterface;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;

/**
 * Vincula un User (Identity BC) ya existente a un Employee sin acceso.
 * Valida que tanto el empleado como el usuario existan antes de persistir.
 */
final readonly class LinkUserToEmployeeHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private UserRepositoryInterface     $userRepository,
        private UserBindingCheckerInterface $bindingChecker,
    ) {
    }

    public function __invoke(LinkUserToEmployeeCommand $command): void
    {
        $employeeId = new EmployeeIdValueObject($command->employeeId);
        $employee   = $this->employeeRepository->findById($employeeId);

        if ($employee === null) {
            throw EmployeeNotFoundException::withId($command->employeeId);
        }

        $userId = new UserIdValueObject($command->userId);

        if ($this->userRepository->findById($userId) === null) {
            throw UserNotFoundException::withId($command->userId);
        }

        $this->bindingChecker->ensureNotBound($userId);

        $employee->linkUser($userId);

        $this->employeeRepository->save($employee);
    }
}
