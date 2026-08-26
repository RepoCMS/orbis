<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\CreateRole;

use App\Identity\Application\Exception\PermissionNotFoundException;
use App\Identity\Application\Exception\RoleNameAlreadyExistsException;
use App\Identity\Domain\Entities\Role;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class CreateRoleHandler
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
        private PermissionRepositoryInterface $permissionRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(CreateRoleCommand $command): void
    {
        $name = new RoleNameValueObject($command->name);
        $slug = new RoleSlugValueObject($command->slug);

        $this->ensureNameIsUnique($name);
        $this->ensureSlugIsUnique($slug);

        $permissionIds = $this->resolvePermissionIds($command->permissionIds);

        $role = Role::create(
            new RoleIdValueObject($this->idGenerator->generate()),
            $name,
            $slug,
            $permissionIds,
        );

        $this->roleRepository->save($role);
    }

    private function ensureSlugIsUnique(RoleSlugValueObject $slug): void
    {
        if ($this->roleRepository->findBySlug($slug) !== null) {
            throw RoleNameAlreadyExistsException::withName((string) $slug);
        }
    }

    private function ensureNameIsUnique(RoleNameValueObject $name): void
    {
        if ($this->roleRepository->findByName($name) !== null) {
            throw RoleNameAlreadyExistsException::withName((string) $name);
        }
    }

    /**
     * @param string[] $ids
     * @return PermissionIdValueObject[]
     */
    private function resolvePermissionIds(array $ids): array
    {
        $permissionIds = array_map(
            static fn (string $id) => new PermissionIdValueObject($id),
            $ids,
        );

        foreach ($permissionIds as $permissionId) {
            if ($this->permissionRepository->findById($permissionId) === null) {
                throw PermissionNotFoundException::withId((string) $permissionId);
            }
        }

        return $permissionIds;
    }
}
