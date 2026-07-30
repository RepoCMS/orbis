<?php

declare(strict_types=1);

namespace App\Support\Application\Query\Response;

final readonly class TicketRescheduleResponse
{
    public function __construct(
        public string  $id,
        public string  $ticketId,
        public string  $previousScheduledAt,
        public string  $newScheduledAt,
        public string  $reason,
        public string  $requestedBy,
        public string  $rescheduledBy,
        public string  $rescheduledAt,
    ) {}

    public static function fromEntity(\App\Support\Domain\Entities\TicketReschedule $reschedule): self
    {
        return new self(
            id: (string) $reschedule->id(),
            ticketId: (string) $reschedule->ticketId(),
            previousScheduledAt: $reschedule->previousScheduledAt()->format('Y-m-d H:i'),
            newScheduledAt: $reschedule->newScheduledAt()->format('Y-m-d H:i'),
            reason: $reschedule->reason(),
            requestedBy: $reschedule->requestedBy()->value,
            rescheduledBy: $reschedule->rescheduledBy(),
            rescheduledAt: $reschedule->rescheduledAt()->format('Y-m-d H:i'),
        );
    }
}
