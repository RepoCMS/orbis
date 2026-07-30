<?php

declare(strict_types=1);

namespace App\Support\Application\Query\Response;

final readonly class TicketAuditLogResponse
{
    public function __construct(
        public string  $id,
        public string  $ticketId,
        public string  $fieldName,
        public ?string $oldValue,
        public ?string $newValue,
        public string  $changedBy,
        public string  $changedAt,
    ) {}

    public static function fromEntity(\App\Support\Domain\Entities\TicketAuditLog $log): self
    {
        return new self(
            id: (string) $log->id(),
            ticketId: $log->ticketId(),
            fieldName: $log->fieldName(),
            oldValue: $log->oldValue(),
            newValue: $log->newValue(),
            changedBy: $log->changedBy(),
            changedAt: $log->changedAt()->format('Y-m-d H:i'),
        );
    }
}
