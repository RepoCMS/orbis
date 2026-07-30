<?php

declare(strict_types=1);

namespace App\Client\Domain\Entities;

use App\Client\Domain\Enums\WarrantyType;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\TerminalWarrantyIdValueObject;
use DateTimeImmutable;

final class TerminalWarranty
{
    private ?DateTimeImmutable $updatedAt = null;

    private function __construct(
        private TerminalWarrantyIdValueObject $id,
        private TerminalIdValueObject $terminalId,
        private WarrantyType $type,
        private DateTimeImmutable $startDate,
        private DateTimeImmutable $endDate,
        private ?string $terms,
        private DateTimeImmutable $createdAt,
    ) {
    }

    public static function register(
        TerminalWarrantyIdValueObject $id,
        TerminalIdValueObject $terminalId,
        WarrantyType $type,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        ?string $terms = null,
    ): self {
        $now = new DateTimeImmutable();

        return new self($id, $terminalId, $type, $startDate, $endDate, $terms, $now);
    }

    public function id(): TerminalWarrantyIdValueObject
    {
        return $this->id;
    }

    public function terminalId(): TerminalIdValueObject
    {
        return $this->terminalId;
    }

    public function type(): WarrantyType
    {
        return $this->type;
    }

    public function startDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function endDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function terms(): ?string
    {
        return $this->terms;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function changeType(WarrantyType $type): void
    {
        $this->type = $type;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function isExpired(): bool
    {
        return new DateTimeImmutable() > $this->endDate;
    }

    public function isAboutToExpire(int $days = 30): bool
    {
        $threshold = new DateTimeImmutable("+{$days} days");

        return !$this->isExpired() && $threshold >= $this->endDate;
    }

    public function extendEndDate(DateTimeImmutable $newEndDate): void
    {
        if ($newEndDate <= $this->endDate) {
            throw new \InvalidArgumentException('La nueva fecha de fin debe ser posterior a la actual.');
        }

        $this->endDate = $newEndDate;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function updateTerms(?string $terms): void
    {
        $this->terms = $terms !== null ? trim($terms) : null;
        $this->updatedAt = new DateTimeImmutable();
    }
}
