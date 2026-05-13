<?php

declare(strict_types=1);

namespace App\Inventory\Application\Query\Response;

use App\Inventory\Domain\Entities\Repair;
use App\Inventory\Domain\Enums\RepairStatus;

final readonly class RepairResponse
{
    public function __construct(
        public string $id,
        public string $serializedStockId,
        public string $laboratoryId,
        public string $assignedById,
        public ?string $technicianId,
        public string $status,
        public string $statusLabel,
        public ?string $notes,
        public string $createdAt,
        public ?string $startedAt,
        public ?string $completedAt,
    ) {
    }

    public static function fromEntity(Repair $repair): self
    {
        $statusLabel = match ($repair->status()) {
            RepairStatus::PENDING      => 'Pendiente',
            RepairStatus::IN_PROGRESS  => 'En proceso',
            RepairStatus::COMPLETED    => 'Completada',
            RepairStatus::UNREPAIRABLE => 'Irreparable',
        };

        return new self(
            id:                (string) $repair->id(),
            serializedStockId: (string) $repair->serializedStockId(),
            laboratoryId:      (string) $repair->laboratoryId(),
            assignedById:      (string) $repair->assignedById(),
            technicianId:      $repair->technicianId() !== null ? (string) $repair->technicianId() : null,
            status:            $repair->status()->value,
            statusLabel:       $statusLabel,
            notes:             $repair->notes(),
            createdAt:         $repair->createdAt()->format('Y-m-d H:i:s'),
            startedAt:         $repair->startedAt()?->format('Y-m-d H:i:s'),
            completedAt:       $repair->completedAt()?->format('Y-m-d H:i:s'),
        );
    }
}
