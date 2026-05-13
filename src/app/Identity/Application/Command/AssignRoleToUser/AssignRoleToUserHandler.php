<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\AssignRoleToUser;

use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Application\Exception\UserNotFoundException;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;

final readonly class AssignRoleToUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function __invoke(AssignRoleToUserCommand $command): void
    {
        $userId = new UserIdValueObject($command->userId);
        $user = $this->userRepository->findById($userId)
            ?? throw UserNotFoundException::withId($command->userId);

        $roleId = new RoleIdValueObject($command->roleId);
        $this->ensureRoleExists($roleId);

        $user->assignRole($roleId);

        $this->userRepository->save($user);
    }

    private function ensureRoleExists(RoleIdValueObject $roleId): void
    {
        if ($this->roleRepository->findById($roleId) === null) {
            throw RoleNotFoundException::withId((string) $roleId);
        }
    }
}
