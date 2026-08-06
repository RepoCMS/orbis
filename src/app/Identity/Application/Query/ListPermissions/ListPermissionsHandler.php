<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\ListPermissions;

use App\Identity\Application\Query\Response\PermissionResponse;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;

final readonly class ListPermissionsHandler
{
    public function __construct(
        private PermissionRepositoryInterface $permissionRepository,
    ) {
    }

    /**
     * @return PermissionResponse[]
     */
    public function __invoke(ListPermissionsQuery $query): array
    {
        $permissions = $this->permissionRepository->findAll();

        return array_map(
            static fn ($permission) => PermissionResponse::fromEntity($permission),
            $permissions,
        );
    }
}
