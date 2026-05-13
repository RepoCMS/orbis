<?php

declare(strict_types=1);

namespace App\Identity\Application\Command\ChangePermissionDescription;

use App\Identity\Application\Exception\PermissionNotFoundException;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;

final readonly class ChangePermissionDescriptionHandler
{
    public function __construct(
        private PermissionRepositoryInterface $permissionRepository,
    ) {
    }

    public function __invoke(ChangePermissionDescriptionCommand $command): void
    {
        $permissionId = new PermissionIdValueObject($command->permissionId);
        $permission = $this->permissionRepository->findById($permissionId)
            ?? throw PermissionNotFoundException::withId($command->permissionId);

        $permission->changeDescription($command->newDescription);

        $this->permissionRepository->save($permission);
    }
}
