<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\Response;

use App\Organization\Domain\Entities\OvertimeRecord;

final readonly class OvertimeRecordResponse
{
    public function __construct(
        public string $id,
        public string $employeeId,
        public string $workedAt,
        public string $startTime,
        public string $endTime,
        public string $reason,
        public ?string $evidenceUrl,
        public string $status,
        public string $statusLabel,
        public ?string $reviewedBy,
        public ?string $rejectionReason,
        public ?string $reviewedAt,
        public string $createdAt,
        public ?string $updatedAt,
    ) {
    }

    public static function fromEntity(OvertimeRecord $record): self
    {
        return new self(
            id:              (string) $record->id(),
            employeeId:      (string) $record->employeeId(),
            workedAt:        $record->workedAt()->format('Y-m-d'),
            startTime:       $record->startTime()->format('H:i'),
            endTime:         $record->endTime()->format('H:i'),
            reason:          $record->reason(),
            evidenceUrl:     $record->evidenceUrl(),
            status:          $record->status()->value,
            statusLabel:     $record->status()->label(),
            reviewedBy:      $record->reviewedBy() !== null ? (string) $record->reviewedBy() : null,
            rejectionReason: $record->rejectionReason(),
            reviewedAt:      $record->reviewedAt()?->format('Y-m-d H:i:s'),
            createdAt:       $record->createdAt()->format('Y-m-d H:i:s'),
            updatedAt:       $record->updatedAt()?->format('Y-m-d H:i:s'),
        );
    }
}
