<?php

declare(strict_types=1);

namespace App\Support\Domain\Entities;

use App\Support\Domain\Enums\WaitingCategory;
use App\Support\Domain\Exceptions\InvalidTimeException;
use App\Support\Domain\ValueObjects\TicketWaitingIdValueObject;

final class TicketWaiting
{
    private function __construct(
        private TicketWaitingIdValueObject $id,
        private string $ticketId,
        private WaitingCategory $category,
        private ?string $subReason,
        private \DateTimeImmutable $startedAt,
        private ?\DateTimeImmutable $endedAt = null,
    ) {
    }

    public static function start(
        TicketWaitingIdValueObject $id,
        string $ticketId,
        WaitingCategory $category,
        ?string $subReason = null,
    ): self {
        if (trim($ticketId) === '') {
            throw new \InvalidArgumentException('El ID del ticket es obligatorio.');
        }

        return new self($id, $ticketId, $category, $subReason, new \DateTimeImmutable(), null);
    }

    public function end(): void
    {
        if ($this->endedAt !== null) {
            throw InvalidTimeException::alreadyPerformed('finalización de espera');
        }
        $this->endedAt = new \DateTimeImmutable();
    }

    public function isActive(): bool
    {
        return $this->endedAt === null;
    }

    public function id(): TicketWaitingIdValueObject
    {
        return $this->id;
    }
    public function ticketId(): string
    {
        return $this->ticketId;
    }
    public function category(): WaitingCategory
    {
        return $this->category;
    }
    public function subReason(): ?string
    {
        return $this->subReason;
    }
    public function startedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }
    public function endedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }
}
