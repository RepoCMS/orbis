<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\Response;

use App\Expense\Domain\Entities\CashAdvance;
use DateTimeImmutable;

/**
 * Detailed response DTO for a single cash advance (show/detail view).
 * Includes all fields plus computed information like voucher path, overdue status.
 */
final class CashAdvanceDetailResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $advanceNumber,
        public readonly string $employeeId,
        public readonly string $givenBy,
        public readonly float $amount,
        public readonly string $givenAt,
        public readonly string $givenAtFormatted,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly ?string $voucherPath,
        public readonly ?string $settlementConfirmedBy,
        public readonly ?string $settlementConfirmedAt,
        public readonly ?string $settlementDirection,
        public readonly int $daysSinceGiven,
        public readonly bool $isOverdue,
        public readonly bool $isEditable,
        public readonly string $createdAt,
        public readonly ?string $updatedAt,
    ) {
    }

    public static function fromEntity(CashAdvance $cashAdvance): self
    {
        $now = new DateTimeImmutable();
        $daysSinceGiven = $now->diff($cashAdvance->givenAt())->days;
        $isOverdue = $cashAdvance->isOverdue(30);

        return new self(
            id: (string) $cashAdvance->id(),
            advanceNumber: $cashAdvance->advanceNumber(),
            employeeId: $cashAdvance->employeeId(),
            givenBy: $cashAdvance->givenBy(),
            amount: $cashAdvance->amount(),
            givenAt: $cashAdvance->givenAt()->format('Y-m-d\TH:i:s\Z'),
            givenAtFormatted: $cashAdvance->givenAt()->format('d/m/Y'),
            status: $cashAdvance->status()->value,
            statusLabel: $cashAdvance->status()->label(),
            voucherPath: $cashAdvance->voucherPath(),
            settlementConfirmedBy: $cashAdvance->settlementConfirmedBy(),
            settlementConfirmedAt: $cashAdvance->settlementConfirmedAt()?->format('Y-m-d\TH:i:s\Z'),
            settlementDirection: $cashAdvance->settlementDirection()?->value,
            daysSinceGiven: $daysSinceGiven,
            isOverdue: $isOverdue,
            isEditable: $cashAdvance->status()->canSettle(),
            createdAt: $cashAdvance->createdAt()->format('Y-m-d\TH:i:s\Z'),
            updatedAt: $cashAdvance->updatedAt()?->format('Y-m-d\TH:i:s\Z'),
        );
    }
}
