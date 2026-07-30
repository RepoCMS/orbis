<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Domain\Enums\RepairPriority;
use App\Inventory\Domain\Enums\RepairStatus;
use App\Inventory\Domain\Exceptions\InvalidLocationTypeException;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\RepairIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use DateTimeImmutable;

/**
 * Tarea de reparación de una pieza serializada.
 *
 * Una Repair vincula un SerializedStock dañado con el laboratorio donde
 * se realizará la reparación. Registra quién asignó la tarea y quién la ejecuta.
 *
 * Ciclo de vida: PENDING → IN_PROGRESS → COMPLETED | UNREPAIRABLE
 *
 * COMPLETED    → la pieza puede devolverse al inventario disponible.
 * UNREPAIRABLE → la pieza debe marcarse como DECOMMISSIONED en SerializedStock.
 */
final class Repair
{
    public function __construct(
        private RepairIdValueObject $id,
        private SerializedStockIdValueObject $serializedStockId,
        private LocationIdValueObject $laboratoryId,
        private UserIdValueObject $assignedById,
        private ?UserIdValueObject $technicianId,
        private RepairStatus $status,
        private RepairPriority $priority,
        private ?string $notes,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $startedAt,
        private ?DateTimeImmutable $completedAt,
    ) {
    }

    public static function assign(
        RepairIdValueObject $id,
        SerializedStockIdValueObject $serializedStockId,
        LocationIdValueObject $laboratoryId,
        UserIdValueObject $assignedById,
        ?string $notes = null,
        RepairPriority $priority = RepairPriority::NORMAL,
    ): self {
        return new self(
            id:               $id,
            serializedStockId: $serializedStockId,
            laboratoryId:     $laboratoryId,
            assignedById:     $assignedById,
            technicianId:     null,
            status:           RepairStatus::PENDING,
            priority:         $priority,
            notes:            $notes,
            createdAt:        new DateTimeImmutable(),
            startedAt:        null,
            completedAt:      null,
        );
    }

    public function id(): RepairIdValueObject
    {
        return $this->id;
    }

    public function serializedStockId(): SerializedStockIdValueObject
    {
        return $this->serializedStockId;
    }

    public function laboratoryId(): LocationIdValueObject
    {
        return $this->laboratoryId;
    }

    public function assignedById(): UserIdValueObject
    {
        return $this->assignedById;
    }

    public function technicianId(): ?UserIdValueObject
    {
        return $this->technicianId;
    }

    public function status(): RepairStatus
    {
        return $this->status;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function priority(): RepairPriority
    {
        return $this->priority;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function startedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function isPending(): bool
    {
        return $this->status === RepairStatus::PENDING;
    }

    public function isInProgress(): bool
    {
        return $this->status === RepairStatus::IN_PROGRESS;
    }

    public function start(UserIdValueObject $technicianId): void
    {
        $this->technicianId = $technicianId;
        $this->status       = RepairStatus::IN_PROGRESS;
        $this->startedAt    = new DateTimeImmutable();
    }

    public function complete(?string $notes = null): void
    {
        $this->status      = RepairStatus::COMPLETED;
        $this->completedAt = new DateTimeImmutable();

        if ($notes !== null) {
            $this->notes = $notes;
        }
    }

    public function markUnrepairable(?string $notes = null): void
    {
        $this->status      = RepairStatus::UNREPAIRABLE;
        $this->completedAt = new DateTimeImmutable();

        if ($notes !== null) {
            $this->notes = $notes;
        }
    }
}
