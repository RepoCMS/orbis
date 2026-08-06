<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\ListRoles;

use App\Identity\Application\Query\Response\RoleResponse;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;

final readonly class ListRolesHandler
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    /**
     * @return RoleResponse[]
     */
    public function __invoke(ListRolesQuery $query): array
    {
        $roles = $this->roleRepository->findAll();

        return array_map(
            static fn ($role) => RoleResponse::fromEntity($role),
            $roles,
        );
    }
}
