<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Support\Domain\ValueObjects\TicketAuditLogIdValueObject;

final class TicketAuditLogIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'ticket_audit_log_id';
    }
    protected function getValueObjectClass(): string
    {
        return TicketAuditLogIdValueObject::class;
    }
}
