<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\DefinePermission;

use App\Identity\Application\Exception\PermissionKeyAlreadyExistsException;
use App\Identity\Domain\Entities\Permission;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;
use App\Identity\Domain\ValueObjects\PermissionKeyValueObject;

final readonly class DefinePermissionHandler
{
    public function __construct(
        private PermissionRepositoryInterface $permissionRepository,
    ) {
    }

    public function __invoke(DefinePermissionCommand $command): void
    {
        $key = new PermissionKeyValueObject($command->key);

        $this->ensureKeyIsUnique($key);

        $permission = Permission::define(
            new PermissionIdValueObject($command->permissionId),
            $key,
            $command->description,
        );

        $this->permissionRepository->save($permission);
    }

    private function ensureKeyIsUnique(PermissionKeyValueObject $key): void
    {
        if ($this->permissionRepository->findByKey($key) !== null) {
            throw PermissionKeyAlreadyExistsException::withKey((string) $key);
        }
    }
}
