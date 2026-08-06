<?php

declare(strict_types=1);

namespace App\Support\Application\Query\Response;

final readonly class TicketJourneyResponse
{
    public function __construct(
        public string  $id,
        public string  $ticketId,
        public string  $type,
        public string  $typeLabel,
        public string  $startedAt,
        public ?string $arrivedAt,
        public bool    $isPartOfRoute,
        public ?string $notes,
    ) {
    }

    public static function fromEntity(\App\Support\Domain\Entities\Journey $journey): self
    {
        $type = $journey->type();

        return new self(
            id: (string) $journey->id(),
            ticketId: (string) $journey->ticketId(),
            type: $type->value,
            typeLabel: $type->label(),
            startedAt: $journey->startedAt()->format('Y-m-d H:i'),
            arrivedAt: $journey->arrivedAt()?->format('Y-m-d H:i'),
            isPartOfRoute: $journey->isPartOfRoute(),
            notes: $journey->notes(),
        );
    }
}
