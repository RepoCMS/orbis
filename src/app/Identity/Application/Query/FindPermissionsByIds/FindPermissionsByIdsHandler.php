<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\FindPermissionsByIds;

use App\Identity\Application\Query\Response\PermissionResponse;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;

final readonly class FindPermissionsByIdsHandler
{
    public function __construct(
        private PermissionRepositoryInterface $permissionRepository,
    ) {
    }

    /**
     * @return PermissionResponse[]
     */
    public function __invoke(FindPermissionsByIdsQuery $query): array
    {
        $ids = array_map(
            static fn(string $id) => new PermissionIdValueObject($id),
            $query->permissionIds,
        );

        $permissions = $this->permissionRepository->findByIds($ids);

        return array_map(
            static fn($permission) => PermissionResponse::fromEntity($permission),
            $permissions,
        );
    }
}
