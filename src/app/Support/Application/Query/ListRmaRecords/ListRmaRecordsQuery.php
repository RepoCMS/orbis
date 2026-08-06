<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListRmaRecords;

final readonly class ListRmaRecordsQuery
{
    public function __construct(
        public ?string $ticketId = null,
    ) {
    }
}
