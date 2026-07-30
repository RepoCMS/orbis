<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\FindRoleBySlug;

use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Application\Query\Response\RoleResponse;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;

final readonly class FindRoleBySlugHandler
{
    public function __construct(
        private RoleRepositoryInterface $roleRepository,
    ) {
    }

    public function __invoke(FindRoleBySlugQuery $query): RoleResponse
    {
        $slug = new RoleSlugValueObject($query->slug);
        $role = $this->roleRepository->findBySlug($slug)
            ?? throw RoleNotFoundException::withId($query->slug);

        return RoleResponse::fromEntity($role);
    }
}
