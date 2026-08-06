<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\RequestItem;
use App\Inventory\Domain\Enums\RequestItemStatus;

final readonly class RequestItemResponse
{
    public function __construct(
        public string $id,
        public string $productId,
        public string $productName,
        public ?string $serializedStockId,
        public int $quantity,
        public string $status,
        public string $statusLabel,
        public ?string $fulfilledMovementId,
    ) {
    }

    public static function fromEntity(RequestItem $item): self
    {
        $statusLabel = match ($item->status()) {
            RequestItemStatus::PENDING   => 'Pendiente',
            RequestItemStatus::FULFILLED => 'Ejecutado',
            RequestItemStatus::CANCELLED => 'Cancelado',
        };

        return new self(
            id:                  (string) $item->id(),
            productId:           (string) $item->productId(),
            productName:         (string) $item->productId(),
            serializedStockId:   $item->serializedStockId() !== null ? (string) $item->serializedStockId() : null,
            quantity:            $item->quantity(),
            status:              $item->status()->value,
            statusLabel:         $statusLabel,
            fulfilledMovementId: $item->fulfilledMovementId() !== null ? (string) $item->fulfilledMovementId() : null,
        );
    }
}
