<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\Response;

use App\Expense\Domain\Entities\CashAdvance;
use DateTimeImmutable;

/**
 * Lightweight DTO for list endpoints (no settlement details).
 */
final class CashAdvanceListItemResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $advanceNumber,
        public readonly string $employeeId,
        public readonly string $givenBy,
        public readonly float $amount,
        public readonly string $givenAt,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly int $daysSinceGiven,
    ) {}

    public static function fromEntity(CashAdvance $cashAdvance): self
    {
        $daysSinceGiven = (new DateTimeImmutable())->diff($cashAdvance->givenAt())->days;

        return new self(
            id: (string) $cashAdvance->id(),
            advanceNumber: $cashAdvance->advanceNumber(),
            employeeId: $cashAdvance->employeeId(),
            givenBy: $cashAdvance->givenBy(),
            amount: $cashAdvance->amount(),
            givenAt: $cashAdvance->givenAt()->format('Y-m-d\TH:i:s\Z'),
            status: $cashAdvance->status()->value,
            statusLabel: $cashAdvance->status()->label(),
            daysSinceGiven: $daysSinceGiven,
        );
    }
}
