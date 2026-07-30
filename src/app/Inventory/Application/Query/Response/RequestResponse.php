<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\Request;
use App\Inventory\Domain\Entities\RequestItem;
use App\Inventory\Domain\Enums\RequestStatus;

final readonly class RequestResponse
{
    public function __construct(
        public string $id,
        public string $reference,
        public string $requestedBy,
        public string $productId,
        public ?string $serializedStockId,
        public int $quantity,
        public string $sourceLocationId,
        public string $destinationLocationId,
        public string $status,
        public string $statusLabel,
        public ?string $notes,
        public ?string $approvedBy,
        public ?string $approvedAt,
        public ?string $rejectedBy,
        public ?string $rejectedAt,
        public ?string $rejectionReason,
        public ?string $fulfilledMovementId,
        public ?string $cancelledBy,
        public string $createdAt,
        public array $items,
        public int $fulfilledItemsCount,
        public int $pendingItemsCount,
    ) {
    }

    public static function fromEntity(Request $request): self
    {
        $statusLabel = match ($request->status()) {
            RequestStatus::PENDING   => 'Pendiente',
            RequestStatus::APPROVED  => 'Aprobada',
            RequestStatus::REJECTED  => 'Rechazada',
            RequestStatus::FULFILLED => 'Ejecutada',
            RequestStatus::CANCELLED => 'Cancelada',
        };

        $items = array_map(
            static fn (RequestItem $item) => RequestItemResponse::fromEntity($item),
            $request->items(),
        );

        $fulfilledCount = 0;
        $pendingCount = 0;
        $firstItem = null;
        foreach ($request->items() as $item) {
            if ($firstItem === null) {
                $firstItem = $item;
            }
            if ($item->isFulfilled()) {
                ++$fulfilledCount;
            } elseif ($item->isPending()) {
                ++$pendingCount;
            }
        }

        return new self(
            id:                   (string) $request->id(),
            reference:            $request->reference(),
            requestedBy:          (string) $request->requestedBy(),
            productId:            $firstItem !== null ? (string) $firstItem->productId() : '',
            serializedStockId:    $firstItem !== null && $firstItem->serializedStockId() !== null ? (string) $firstItem->serializedStockId() : null,
            quantity:             $firstItem !== null ? $firstItem->quantity() : 0,
            sourceLocationId:     (string) $request->sourceLocationId(),
            destinationLocationId: (string) $request->destinationLocationId(),
            status:               $request->status()->value,
            statusLabel:          $statusLabel,
            notes:                $request->notes(),
            approvedBy:           $request->approvedBy() !== null ? (string) $request->approvedBy() : null,
            approvedAt:           $request->approvedAt()?->format('Y-m-d H:i:s'),
            rejectedBy:           $request->rejectedBy() !== null ? (string) $request->rejectedBy() : null,
            rejectedAt:           $request->rejectedAt()?->format('Y-m-d H:i:s'),
            rejectionReason:      $request->rejectionReason(),
            fulfilledMovementId:  null,
            cancelledBy:          $request->cancelledBy() !== null ? (string) $request->cancelledBy() : null,
            createdAt:            $request->createdAt()->format('Y-m-d H:i:s'),
            items:                $items,
            fulfilledItemsCount:  $fulfilledCount,
            pendingItemsCount:    $pendingCount,
        );
    }
}
