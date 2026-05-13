<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Domain\Enums\RequestStatus;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use DateTimeImmutable;

/**
 * Solicitud formal de movimiento de stock.
 *
 * Un técnico solicita repuestos desde almacén/laboratorio hacia su mochila.
 * El flujo requiere aprobación antes de generar el Movement correspondiente.
 *
 * Ciclo: PENDING → APPROVED → FULFILLED
 *                └──────────→ REJECTED
 *        PENDING → CANCELLED
 */
final class Request
{
    public function __construct(
        private RequestIdValueObject $id,
        private string $reference,
        private UserIdValueObject $requestedBy,
        private ProductIdValueObject $productId,
        private ?SerializedStockIdValueObject $serializedStockId,
        private int $quantity,
        private LocationIdValueObject $sourceLocationId,
        private LocationIdValueObject $destinationLocationId,
        private RequestStatus $status,
        private ?string $notes,
        private ?UserIdValueObject $approvedBy,
        private ?DateTimeImmutable $approvedAt,
        private ?UserIdValueObject $rejectedBy,
        private ?DateTimeImmutable $rejectedAt,
        private ?string $rejectionReason,
        private ?MovementIdValueObject $fulfilledMovementId,
        private ?UserIdValueObject $cancelledBy,
        private DateTimeImmutable $createdAt,
    ) {
    }

    public static function submit(
        RequestIdValueObject $id,
        string $reference,
        UserIdValueObject $requestedBy,
        ProductIdValueObject $productId,
        ?SerializedStockIdValueObject $serializedStockId,
        int $quantity,
        LocationIdValueObject $sourceLocationId,
        LocationIdValueObject $destinationLocationId,
        ?string $notes = null,
    ): self {
        return new self(
            id:                   $id,
            reference:            $reference,
            requestedBy:          $requestedBy,
            productId:            $productId,
            serializedStockId:    $serializedStockId,
            quantity:             $quantity,
            sourceLocationId:     $sourceLocationId,
            destinationLocationId: $destinationLocationId,
            status:               RequestStatus::PENDING,
            notes:                $notes,
            approvedBy:           null,
            approvedAt:           null,
            rejectedBy:           null,
            rejectedAt:           null,
            rejectionReason:      null,
            fulfilledMovementId:  null,
            cancelledBy:          null,
            createdAt:            new DateTimeImmutable(),
        );
    }

    public function id(): RequestIdValueObject
    {
        return $this->id;
    }

    public function reference(): string
    {
        return $this->reference;
    }

    public function requestedBy(): UserIdValueObject
    {
        return $this->requestedBy;
    }

    public function productId(): ProductIdValueObject
    {
        return $this->productId;
    }

    public function serializedStockId(): ?SerializedStockIdValueObject
    {
        return $this->serializedStockId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function sourceLocationId(): LocationIdValueObject
    {
        return $this->sourceLocationId;
    }

    public function destinationLocationId(): LocationIdValueObject
    {
        return $this->destinationLocationId;
    }

    public function status(): RequestStatus
    {
        return $this->status;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function approvedBy(): ?UserIdValueObject
    {
        return $this->approvedBy;
    }

    public function approvedAt(): ?DateTimeImmutable
    {
        return $this->approvedAt;
    }

    public function rejectedBy(): ?UserIdValueObject
    {
        return $this->rejectedBy;
    }

    public function rejectedAt(): ?DateTimeImmutable
    {
        return $this->rejectedAt;
    }

    public function rejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function fulfilledMovementId(): ?MovementIdValueObject
    {
        return $this->fulfilledMovementId;
    }

    public function cancelledBy(): ?UserIdValueObject
    {
        return $this->cancelledBy;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function approve(UserIdValueObject $approvedBy): void
    {
        if ($this->status !== RequestStatus::PENDING) {
            throw new \LogicException('Solo se pueden aprobar solicitudes en estado PENDING.');
        }

        $this->status     = RequestStatus::APPROVED;
        $this->approvedBy = $approvedBy;
        $this->approvedAt = new DateTimeImmutable();
    }

    public function reject(UserIdValueObject $rejectedBy, string $reason): void
    {
        if ($this->status !== RequestStatus::PENDING) {
            throw new \LogicException('Solo se pueden rechazar solicitudes en estado PENDING.');
        }

        $this->status          = RequestStatus::REJECTED;
        $this->rejectedBy      = $rejectedBy;
        $this->rejectedAt      = new DateTimeImmutable();
        $this->rejectionReason = $reason;
    }

    public function fulfill(MovementIdValueObject $movementId): void
    {
        if ($this->status !== RequestStatus::APPROVED && $this->status !== RequestStatus::AWAITING_TECHNICIAN_ACCEPTANCE) {
            throw new \LogicException('Solo se pueden ejecutar solicitudes en estado APPROVED o AWAITING_TECHNICIAN_ACCEPTANCE.');
        }

        $this->status              = RequestStatus::FULFILLED;
        $this->fulfilledMovementId = $movementId;
    }

    public function notifyTechnicianAcceptance(): void
    {
        if ($this->status !== RequestStatus::APPROVED) {
            throw new \LogicException('Solo se puede notificar al técnico desde estado APPROVED.');
        }

        $this->status = RequestStatus::AWAITING_TECHNICIAN_ACCEPTANCE;
    }

    public function acceptByTechnician(MovementIdValueObject $movementId): void
    {
        if ($this->status !== RequestStatus::AWAITING_TECHNICIAN_ACCEPTANCE) {
            throw new \LogicException('Solo se puede aceptar desde estado AWAITING_TECHNICIAN_ACCEPTANCE.');
        }

        $this->status              = RequestStatus::FULFILLED;
        $this->fulfilledMovementId = $movementId;
    }

    public function rejectByTechnician(UserIdValueObject $rejectedBy, string $reason): void
    {
        if ($this->status !== RequestStatus::AWAITING_TECHNICIAN_ACCEPTANCE) {
            throw new \LogicException('Solo se puede rechazar (técnico) desde estado AWAITING_TECHNICIAN_ACCEPTANCE.');
        }

        $this->status          = RequestStatus::REJECTED;
        $this->rejectedBy      = $rejectedBy;
        $this->rejectedAt      = new DateTimeImmutable();
        $this->rejectionReason = $reason;
    }

    public function cancel(UserIdValueObject $cancelledBy): void
    {
        if ($this->status !== RequestStatus::PENDING) {
            throw new \LogicException('Solo se pueden cancelar solicitudes en estado PENDING.');
        }

        $this->status      = RequestStatus::CANCELLED;
        $this->cancelledBy = $cancelledBy;
    }
}
