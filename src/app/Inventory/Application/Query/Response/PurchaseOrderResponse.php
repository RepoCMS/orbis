<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\PurchaseOrder;
use App\Inventory\Domain\Enums\PurchaseOrderStatus;

final readonly class PurchaseOrderResponse
{
    public function __construct(
        public string $id,
        public string $reference,
        public string $status,
        public string $statusLabel,
        public ?string $notes,
        public string $createdBy,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(PurchaseOrder $po): self
    {
        $statusLabel = match ($po->status()) {
            PurchaseOrderStatus::OPEN      => 'Abierta',
            PurchaseOrderStatus::PARTIAL   => 'Parcial',
            PurchaseOrderStatus::COMPLETED => 'Completada',
            PurchaseOrderStatus::CANCELLED => 'Cancelada',
        };

        return new self(
            id:          (string) $po->id(),
            reference:   $po->reference(),
            status:      $po->status()->value,
            statusLabel: $statusLabel,
            notes:       $po->notes(),
            createdBy:   (string) $po->createdBy(),
            createdAt:   $po->createdAt()->format('Y-m-d H:i:s'),
        );
    }
}
