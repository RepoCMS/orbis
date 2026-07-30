<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\DeleteRole;

use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Domain\Exceptions\RoleStatusException;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use DomainException;

final readonly class DeleteRoleHandler
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(DeleteRoleCommand $command): void
    {
        $roleId = new RoleIdValueObject($command->roleId);
        $role = $this->roleRepository->findById($roleId)
            ?? throw RoleNotFoundException::withId($command->roleId);

        $assignedUsersCount = $this->userRepository->countByRoleId($roleId);

        if ($assignedUsersCount > 0) {
            throw new DomainException(
                sprintf(
                    'No se puede eliminar el rol "%s": tiene %d usuario(s) asignado(s).',
                    (string) $role->name(),
                    $assignedUsersCount,
                ),
            );
        }

        if (!$role->isActive()) {
            throw new RoleStatusException('El rol ya se encuentra inactivo.');
        }

        $role->deactivate();

        $this->roleRepository->save($role);
    }
}
