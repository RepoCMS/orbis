<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\ActivateRole;

use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;

final readonly class ActivateRoleHandler
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function __invoke(ActivateRoleCommand $command): void
    {
        $roleId = new RoleIdValueObject($command->roleId);
        $role = $this->roleRepository->findById($roleId)
            ?? throw RoleNotFoundException::withId($command->roleId);

        $role->activate();

        $this->roleRepository->save($role);
    }
}
