<?php

declare(strict_types=1);

namespace App\Support\Domain\Repositories;

use App\Support\Domain\Entities\TicketAuditLog;
use App\Support\Domain\ValueObjects\TicketAuditLogIdValueObject;

interface TicketAuditLogRepositoryInterface
{
    public function findById(TicketAuditLogIdValueObject $id): ?TicketAuditLog;

    /** @return TicketAuditLog[] */
    public function findByTicketId(string $ticketId): array;

    public function save(TicketAuditLog $auditLog): void;
}
