<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\FindRoleById;

use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Application\Query\Response\RoleResponse;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\RoleIdValueObject;

final readonly class FindRoleByIdHandler
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function __invoke(FindRoleByIdQuery $query): RoleResponse
    {
        $roleId = new RoleIdValueObject($query->roleId);
        $role = $this->roleRepository->findById($roleId)
            ?? throw RoleNotFoundException::withId($query->roleId);

        return RoleResponse::fromEntity($role);
    }
}
