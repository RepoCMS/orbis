<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Service;

use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Identity\Domain\Exceptions\UserAlreadyBoundException;
use App\Identity\Domain\Service\UserBindingCheckerInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;

/**
 * Implementación que cruza los repos de Contact y Employee
 * para garantizar la unicidad del vínculo User ↔ Persona.
 */
final readonly class UserBindingChecker implements UserBindingCheckerInterface
{
    public function __construct(
        private ContactRepositoryInterface  $contactRepository,
        private EmployeeRepositoryInterface $employeeRepository,
    ) {
    }

    public function ensureNotBound(UserIdValueObject $userId): void
    {
        if ($this->contactRepository->findByUserId($userId) !== null) {
            throw UserAlreadyBoundException::withUserId(
                (string) $userId,
                'un contacto de cliente',
            );
        }

        if ($this->employeeRepository->findByUserId($userId) !== null) {
            throw UserAlreadyBoundException::withUserId(
                (string) $userId,
                'un empleado',
            );
        }
    }
}
