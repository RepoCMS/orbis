<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\TechnicianLoan;

final readonly class TechnicianLoanResponse
{
    public function __construct(
        public string $id,
        public string $serializedStockId,
        public string $fromTechnicianId,
        public string $toTechnicianId,
        public ?string $ticketId,
        public string $status,
        public string $statusLabel,
        public ?string $notes,
        public ?string $approvedBy,
        public ?string $approvedAt,
        public ?string $rejectedBy,
        public ?string $rejectedAt,
        public ?string $rejectionReason,
        public ?string $cancelledBy,
        public string $createdAt,
        public ?string $updatedAt,
    ) {
    }

    public static function fromEntity(TechnicianLoan $loan): self
    {
        return new self(
            id:                (string) $loan->id(),
            serializedStockId: $loan->serializedStockId(),
            fromTechnicianId:  $loan->fromTechnicianId(),
            toTechnicianId:    $loan->toTechnicianId(),
            ticketId:          $loan->ticketId(),
            status:            $loan->status()->value,
            statusLabel:       $loan->status()->label(),
            notes:             $loan->notes(),
            approvedBy:        $loan->approvedBy(),
            approvedAt:        $loan->approvedAt()?->format('c'),
            rejectedBy:        $loan->rejectedBy(),
            rejectedAt:        $loan->rejectedAt()?->format('c'),
            rejectionReason:   $loan->rejectionReason(),
            cancelledBy:       $loan->cancelledBy(),
            createdAt:         $loan->createdAt()->format('c'),
            updatedAt:         $loan->updatedAt()?->format('c'),
        );
    }
}
