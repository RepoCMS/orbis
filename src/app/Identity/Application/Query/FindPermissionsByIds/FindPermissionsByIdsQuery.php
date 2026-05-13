<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\FindPermissionsByIds;

final readonly class FindPermissionsByIdsQuery
{
    /**
     * @param string[] $permissionIds
     */
    public function __construct(
        public array $permissionIds,
    ) {
    }
}
