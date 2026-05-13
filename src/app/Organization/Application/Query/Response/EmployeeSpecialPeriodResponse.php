<?php

declare(strict_types=1);

namespace App\Organization\Application\Query\Response;

use App\Organization\Domain\Entities\EmployeeSpecialPeriod;

final readonly class EmployeeSpecialPeriodResponse
{
    public function __construct(
        public string $id,
        public string $type,
        public string $typeLabel,
        public string $status,
        public string $statusLabel,
        public bool   $isActive,
        public string $startedAt,
        public string $endedAt,
        public int    $days,
        public ?string $notes,
    ) {
    }

    public static function fromEntity(EmployeeSpecialPeriod $period): self
    {
        return new self(
            id:          (string) $period->id(),
            type:        $period->type()->value,
            typeLabel:   $period->type()->label(),
            status:      $period->status()->value,
            statusLabel: $period->status()->label(),
            isActive:    $period->status()->isActive(),
            startedAt:   $period->startedAt()->format('Y-m-d'),
            endedAt:     $period->endedAt()->format('Y-m-d'),
            days:        $period->days(),
            notes:       $period->notes(),
        );
    }
}
