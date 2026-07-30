<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\PhysicalCount;

final readonly class PhysicalCountResponse
{
    /**
     * @param PhysicalCountItemResponse[] $items
     */
    public function __construct(
        public string $id,
        public string $locationId,
        public string $countedBy,
        public ?string $notes,
        public string $status,
        public string $statusLabel,
        public ?string $approvedBy,
        public ?string $rejectionReason,
        public ?string $approvedAt,
        public ?string $rejectedAt,
        public string $createdAt,
        public ?string $updatedAt,
        public array $items = [],
    ) {
    }

    public static function fromEntity(PhysicalCount $count): self
    {
        $items = array_map(
            static fn($item) => PhysicalCountItemResponse::fromEntity($item),
            $count->items(),
        );

        return new self(
            id:              (string) $count->id(),
            locationId:      $count->locationId(),
            countedBy:       $count->countedBy(),
            notes:           $count->notes(),
            status:          $count->status()->value,
            statusLabel:     $count->status()->label(),
            approvedBy:      $count->approvedBy(),
            rejectionReason: $count->rejectionReason(),
            approvedAt:      $count->approvedAt()?->format('Y-m-d\TH:i:s'),
            rejectedAt:      $count->rejectedAt()?->format('Y-m-d\TH:i:s'),
            createdAt:       $count->createdAt()->format('Y-m-d\TH:i:s'),
            updatedAt:       $count->updatedAt()?->format('Y-m-d\TH:i:s'),
            items:           $items,
        );
    }

    /** @param PhysicalCount[] $counts */
    public static function fromEntities(array $counts): array
    {
        return array_map(
            static fn(PhysicalCount $count) => self::fromEntity($count),
            $counts,
        );
    }
}
