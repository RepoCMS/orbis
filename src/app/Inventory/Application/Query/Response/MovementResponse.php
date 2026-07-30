<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Enums\MovementStatus;
use App\Inventory\Domain\Enums\MovementType;

final readonly class MovementResponse
{
    public function __construct(
        public string  $id,
        public string  $productId,
        public string  $type,
        public string  $typeLabel,
        public ?string $sourceLocationId,
        public ?string $destinationLocationId,
        public ?string $serializedStockId,
        public int     $quantity,
        public string  $status,
        public string  $statusLabel,
        public ?string $notes,
        public string  $createdAt,
        public ?string $completedAt,
        public ?string $createdByUserId = null,
        public ?string $assignedToUserId = null,
        public ?string $rejectedAt = null,
        public ?string $rejectionReason = null,
    ) {
    }

    public static function fromEntity(Movement $movement): self
    {
        $typeLabel = match ($movement->type()) {
            MovementType::ENTRY    => 'Entrada',
            MovementType::TRANSFER => 'Traslado',
            MovementType::EXIT     => 'Salida',
        };

        $statusLabel = match ($movement->status()) {
            MovementStatus::PENDING   => 'Pendiente',
            MovementStatus::IN_TRANSIT => 'En tránsito',
            MovementStatus::COMPLETED => 'Completado',
            MovementStatus::CANCELLED => 'Cancelado',
            MovementStatus::REJECTED  => 'Rechazado',
        };

        return new self(
            id:                   (string) $movement->id(),
            productId:            (string) $movement->productId(),
            type:                 $movement->type()->value,
            typeLabel:            $typeLabel,
            sourceLocationId:     $movement->sourceLocationId() !== null ? (string) $movement->sourceLocationId() : null,
            destinationLocationId: $movement->destinationLocationId() !== null ? (string) $movement->destinationLocationId() : null,
            serializedStockId:    $movement->serializedStockId() !== null ? (string) $movement->serializedStockId() : null,
            quantity:             $movement->quantity(),
            status:               $movement->status()->value,
            statusLabel:          $statusLabel,
            notes:                $movement->notes(),
            createdAt:            $movement->createdAt()->format('Y-m-d H:i:s'),
            completedAt:          $movement->completedAt()?->format('Y-m-d H:i:s'),
            createdByUserId:      $movement->createdByUserId(),
            assignedToUserId:    $movement->assignedToUserId(),
            rejectedAt:          $movement->rejectedAt()?->format('Y-m-d H:i:s'),
            rejectionReason:     $movement->rejectionReason(),
        );
    }
}
