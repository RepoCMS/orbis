<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\FindRoleByName;

use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Application\Query\Response\RoleResponse;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;

final readonly class FindRoleByNameHandler
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function __invoke(FindRoleByNameQuery $query): RoleResponse
    {
        $name = new RoleNameValueObject($query->name);
        $role = $this->roleRepository->findByName($name)
            ?? throw RoleNotFoundException::withId($query->name);

        return RoleResponse::fromEntity($role);
    }
}
