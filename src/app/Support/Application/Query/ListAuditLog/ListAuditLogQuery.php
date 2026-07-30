<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListAuditLog;

final readonly class ListAuditLogQuery
{
    public function __construct(
        public string $ticketId,
    ) {}
}
