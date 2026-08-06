<?php

declare(strict_types=1);

namespace App\Expense\Application\Query\Response;

use App\Expense\Domain\Entities\ExpenseItem;

final class ExpenseItemResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $typeLabel,
        public readonly float $amount,
        public readonly array $ticketIds,
        public readonly ?string $evidencePath,
        public readonly bool $evidenceRequired,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly ?string $notes,
        public readonly ?string $rejectionReason,
        public readonly ?string $reviewedBy,
        public readonly ?string $reviewedAt,
    ) {
    }

    public static function fromEntity(ExpenseItem $item): self
    {
        return new self(
            id: (string) $item->id(),
            type: $item->type()->value,
            typeLabel: $item->type()->label(),
            amount: $item->amount(),
            ticketIds: $item->ticketIds(),
            evidencePath: $item->evidencePath(),
            evidenceRequired: $item->evidenceRequired(),
            status: $item->status()->value,
            statusLabel: $item->status()->label(),
            notes: $item->notes(),
            rejectionReason: $item->rejectionReason(),
            reviewedBy: $item->reviewedBy(),
            reviewedAt: $item->reviewedAt()?->format('Y-m-d\TH:i:s\Z'),
        );
    }
}
