<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Service;

use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Identity\Domain\Exceptions\UserOrphanException;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\Service\OrphanUserCheckerInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Organization\Domain\Repositories\EmployeeRepositoryInterface;

/**
 * Implementación que verifica si un User está vinculado a un Employee o Contact.
 * También permite encontrar Users huérfanos (sin Employee ni Contact vinculado).
 */
final readonly class OrphanUserChecker implements OrphanUserCheckerInterface
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private ContactRepositoryInterface $contactRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function ensureIsBound(UserIdValueObject $userId): void
    {
        if (!$this->isBound($userId)) {
            throw UserOrphanException::withUserId((string) $userId);
        }
    }

    public function isBound(UserIdValueObject $userId): bool
    {
        $userIdStr = (string) $userId;

        return $this->employeeRepository->findByUserId($userIdStr) !== null
            || $this->contactRepository->findByUserId($userIdStr) !== null;
    }

    /** @return string[] */
    public function findAllOrphanUserIds(): array
    {
        $orphanIds = [];

        // Para MVP con <5000 usuarios, findAll() + filter en memoria es aceptable.
        foreach ($this->userRepository->findAll() as $user) {
            if ($user->isDeleted()) {
                continue; // excluir soft-deleted
            }

            if (!$this->isBound(new UserIdValueObject((string) $user->id()))) {
                $orphanIds[] = (string) $user->id();
            }
        }

        return $orphanIds;
    }
}
