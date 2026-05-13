<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\FindPermissionById;

use App\Identity\Application\Exception\PermissionNotFoundException;
use App\Identity\Application\Query\Response\PermissionResponse;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionIdValueObject;

final readonly class FindPermissionByIdHandler
{
    public function __construct(
        private PermissionRepositoryInterface $permissionRepository,
    ) {
    }

    public function __invoke(FindPermissionByIdQuery $query): PermissionResponse
    {
        $permissionId = new PermissionIdValueObject($query->permissionId);
        $permission = $this->permissionRepository->findById($permissionId)
            ?? throw PermissionNotFoundException::withId($query->permissionId);

        return PermissionResponse::fromEntity($permission);
    }
}
