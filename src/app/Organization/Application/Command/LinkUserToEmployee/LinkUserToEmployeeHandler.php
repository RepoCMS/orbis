<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\LinkUserToEmployee;

use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Application\Service\EmployeeUserInvariantService;
use App\Organization\Domain\Events\EmployeeUserLinked;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

/**
 * Vincula un User (Identity BC) ya existente a un Employee sin acceso.
 * Valida que tanto el empleado como el usuario existan antes de persistir
 * y que ninguno esté ya vinculado en el otro lado.
 */
final readonly class LinkUserToEmployeeHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private UserRepositoryInterface $userRepository,
        private EmployeeUserInvariantService $invariantService,
        private DomainEventPublisherInterface $publisher,
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
        $user   = $this->userRepository->findById($userId);

        if ($user === null) {
            throw UserNotFoundException::withId($command->userId);
        }

        $this->invariantService->validateCanLink($employee, $command->userId);

        $employee->linkUser($userId);
        $user->linkEmployee($command->employeeId);

        $this->employeeRepository->save($employee);
        $this->userRepository->save($user);

        $this->publisher->publish(new EmployeeUserLinked(
            employeeId: $command->employeeId,
            userId: $command->userId,
        ));
    }
}
