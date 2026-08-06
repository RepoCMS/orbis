<?php

declare(strict_types=1);

namespace App\Support\Domain\Entities;

use App\Support\Domain\ValueObjects\TicketIdValueObject;
use App\Support\Domain\ValueObjects\TicketPauseIdValueObject;

final class TicketPause
{
    public function __construct(
        private TicketPauseIdValueObject $id,
        private TicketIdValueObject      $ticketId,
        private string                   $pauseReason,
        private \DateTimeImmutable       $pausedAt,
        private ?\DateTimeImmutable      $resumedAt,
    ) {
    }

    public static function start(
        TicketPauseIdValueObject $id,
        TicketIdValueObject $ticketId,
        string $pauseReason,
    ): self {
        if (trim($pauseReason) === '') {
            throw new \InvalidArgumentException('El motivo de la pausa es obligatorio.');
        }

        return new self($id, $ticketId, $pauseReason, new \DateTimeImmutable(), null);
    }

    public function resume(): void
    {
        if ($this->resumedAt !== null) {
            throw new \LogicException('La pausa ya fue reanudada.');
        }
        $this->resumedAt = new \DateTimeImmutable();
    }

    public function isActive(): bool
    {
        return $this->resumedAt === null;
    }

    public function id(): TicketPauseIdValueObject
    {
        return $this->id;
    }
    public function ticketId(): TicketIdValueObject
    {
        return $this->ticketId;
    }
    public function pauseReason(): string
    {
        return $this->pauseReason;
    }
    public function pausedAt(): \DateTimeImmutable
    {
        return $this->pausedAt;
    }
    public function resumedAt(): ?\DateTimeImmutable
    {
        return $this->resumedAt;
    }
}
