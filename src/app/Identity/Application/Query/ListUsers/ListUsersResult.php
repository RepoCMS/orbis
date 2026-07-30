<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\ListUsers;

use App\Identity\Application\Query\Response\UserResponse;

final readonly class ListUsersResult
{
    /**
     * @param UserResponse[] $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $limit,
    ) {
    }
}
