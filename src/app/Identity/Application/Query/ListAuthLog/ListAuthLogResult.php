<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\ListAuthLog;

use App\Identity\Application\Query\Response\AuthLogResponse;

final readonly class ListAuthLogResult
{
    /**
     * @param AuthLogResponse[] $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $limit,
    ) {
    }
}
