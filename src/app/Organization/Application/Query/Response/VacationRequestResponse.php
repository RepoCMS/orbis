<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\Response;

use App\Organization\Domain\Entities\VacationRequest;

final readonly class VacationRequestResponse
{
    public function __construct(
        public string $id,
        public string $employeeId,
        public string $startDate,
        public string $endDate,
        public string $status,
        public string $statusLabel,
        public ?string $reviewedBy,
        public ?string $rejectionReason,
        public ?string $reviewedAt,
        public string $createdAt,
        public ?string $updatedAt,
    ) {
    }

    public static function fromEntity(VacationRequest $request): self
    {
        return new self(
            id:              (string) $request->id(),
            employeeId:      (string) $request->employeeId(),
            startDate:       $request->startDate()->format('Y-m-d'),
            endDate:         $request->endDate()->format('Y-m-d'),
            status:          $request->status()->value,
            statusLabel:     $request->status()->label(),
            reviewedBy:      $request->reviewedBy() !== null ? (string) $request->reviewedBy() : null,
            rejectionReason: $request->rejectionReason(),
            reviewedAt:      $request->reviewedAt()?->format('Y-m-d H:i:s'),
            createdAt:       $request->createdAt()->format('Y-m-d H:i:s'),
            updatedAt:       $request->updatedAt()?->format('Y-m-d H:i:s'),
        );
    }
}
