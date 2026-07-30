<?php

declare(strict_types=1);

namespace App\Support\Application\Query\Response;

final readonly class TicketPauseResponse
{
    public function __construct(
        public string  $id,
        public string  $ticketId,
        public string  $pauseReason,
        public string  $pausedAt,
        public ?string $resumedAt,
    ) {}

    public static function fromEntity(\App\Support\Domain\Entities\TicketPause $pause): self
    {
        return new self(
            id: (string) $pause->id(),
            ticketId: (string) $pause->ticketId(),
            pauseReason: $pause->pauseReason(),
            pausedAt: $pause->pausedAt()->format('Y-m-d H:i'),
            resumedAt: $pause->resumedAt()?->format('Y-m-d H:i'),
        );
    }
}
