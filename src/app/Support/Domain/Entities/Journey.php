<?php

declare(strict_types=1);

namespace App\Support\Domain\Entities;

use App\Support\Domain\Enums\JourneyType;
use App\Support\Domain\ValueObjects\JourneyIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final class Journey
{
    public function __construct(
        private JourneyIdValueObject $id,
        private TicketIdValueObject  $ticketId,
        private JourneyType          $type,
        private \DateTimeImmutable   $startedAt,
        private ?\DateTimeImmutable  $arrivedAt,
        private bool                 $isPartOfRoute,
        private ?string              $notes,
    ) {}

    public static function start(
        JourneyIdValueObject $id,
        TicketIdValueObject $ticketId,
        JourneyType $type,
        bool $isPartOfRoute = false,
        ?string $notes = null,
    ): self {
        return new self(
            id: $id,
            ticketId: $ticketId,
            type: $type,
            startedAt: new \DateTimeImmutable(),
            arrivedAt: null,
            isPartOfRoute: $isPartOfRoute,
            notes: $notes,
        );
    }

    public function arrive(): void
    {
        if ($this->arrivedAt !== null) {
            throw new \LogicException('El viaje ya tiene registrada una llegada.');
        }
        $this->arrivedAt = new \DateTimeImmutable();
    }

    public function id(): JourneyIdValueObject       { return $this->id; }
    public function ticketId(): TicketIdValueObject   { return $this->ticketId; }
    public function type(): JourneyType               { return $this->type; }
    public function startedAt(): \DateTimeImmutable   { return $this->startedAt; }
    public function arrivedAt(): ?\DateTimeImmutable  { return $this->arrivedAt; }
    public function isPartOfRoute(): bool             { return $this->isPartOfRoute; }
    public function notes(): ?string                  { return $this->notes; }
}
