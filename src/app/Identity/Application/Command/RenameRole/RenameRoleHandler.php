<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\RenameRole;

use App\Identity\Application\Exception\RoleNameAlreadyExistsException;
use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;

final readonly class RenameRoleHandler
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function __invoke(RenameRoleCommand $command): void
    {
        $roleId = new RoleIdValueObject($command->roleId);
        $role = $this->roleRepository->findById($roleId)
            ?? throw RoleNotFoundException::withId($command->roleId);

        $newName = new RoleNameValueObject($command->newName);

        $this->ensureNameIsUnique($newName);

        $role->rename($newName);

        $this->roleRepository->save($role);
    }

    private function ensureNameIsUnique(RoleNameValueObject $name): void
    {
        if ($this->roleRepository->findByName($name) !== null) {
            throw RoleNameAlreadyExistsException::withName((string) $name);
        }
    }
}
