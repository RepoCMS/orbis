<?php

declare(strict_types=1);

namespace App\Organization\Application\Service;

use App\Identity\Domain\Exceptions\UserAlreadyBoundException;
use App\Identity\Domain\Service\UserBindingCheckerInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Organization\Domain\Entities\Employee;
use App\Organization\Domain\Exceptions\EmployeeAlreadyHasUserException;

final readonly class EmployeeUserInvariantService
{
    public function __construct(
        private UserBindingCheckerInterface $bindingChecker,
    ) {
    }

    public function validateCanLink(Employee $employee, string $userId): void
    {
        if ($employee->hasUser()) {
            throw EmployeeAlreadyHasUserException::withId((string) $employee->id());
        }

        $this->bindingChecker->ensureNotBound(new UserIdValueObject($userId));
    }

    public function validateCanUnlink(Employee $employee): void
    {
        if (!$employee->hasUser()) {
            throw EmployeeAlreadyHasUserException::withId((string) $employee->id());
        }
    }
}
