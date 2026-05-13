<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\FindPermissionByKey;

use App\Identity\Application\Exception\PermissionNotFoundException;
use App\Identity\Application\Query\Response\PermissionResponse;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionKeyValueObject;

final readonly class FindPermissionByKeyHandler
{
    public function __construct(
        private PermissionRepositoryInterface $permissionRepository,
    ) {
    }

    public function __invoke(FindPermissionByKeyQuery $query): PermissionResponse
    {
        $key = new PermissionKeyValueObject($query->key);
        $permission = $this->permissionRepository->findByKey($key)
            ?? throw PermissionNotFoundException::withId($query->key);

        return PermissionResponse::fromEntity($permission);
    }
}
