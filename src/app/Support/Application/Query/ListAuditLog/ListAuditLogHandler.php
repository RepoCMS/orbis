<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListAuditLog;

use App\Support\Application\Query\Response\TicketAuditLogResponse;
use App\Support\Domain\Repositories\TicketAuditLogRepositoryInterface;

final readonly class ListAuditLogHandler
{
    public function __construct(
        private TicketAuditLogRepositoryInterface $auditLogRepo,
    ) {
    }

    /** @return TicketAuditLogResponse[] */
    public function __invoke(ListAuditLogQuery $query): array
    {
        $logs = $this->auditLogRepo->findByTicketId($query->ticketId);

        return array_map(
            fn ($log) => TicketAuditLogResponse::fromEntity($log),
            $logs
        );
    }
}
