<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\ListAuthLog;

final readonly class ListAuthLogQuery
{
    public function __construct(
        public ?string $userId = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public int $page = 1,
        public int $limit = 20,
    ) {
    }
}
