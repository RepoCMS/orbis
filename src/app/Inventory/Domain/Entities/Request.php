<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Domain\Enums\RequestStatus;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestItemIdValueObject;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

final class Request
{
    /** @var Collection<int, RequestItem> */
    private Collection $items;

    private function __construct(
        private RequestIdValueObject $id,
        private string $reference,
        private UserIdValueObject $requestedBy,
        private LocationIdValueObject $sourceLocationId,
        private LocationIdValueObject $destinationLocationId,
        private RequestStatus $status,
        private ?string $notes,
        private ?UserIdValueObject $approvedBy,
        private ?DateTimeImmutable $approvedAt,
        private ?UserIdValueObject $rejectedBy,
        private ?DateTimeImmutable $rejectedAt,
        private ?string $rejectionReason,
        private ?UserIdValueObject $cancelledBy,
        private DateTimeImmutable $createdAt,
        array $items = [],
    ) {
        $this->items = new ArrayCollection();

        foreach ($items as $item) {
            $this->items->add($item);
            $item->setRequest($this);
        }
    }

    public static function submit(
        RequestIdValueObject $id,
        string $reference,
        UserIdValueObject $requestedBy,
        LocationIdValueObject $sourceLocationId,
        LocationIdValueObject $destinationLocationId,
        ?string $notes = null,
        array $items = [],
    ): self {
        return new self(
            id:                   $id,
            reference:            $reference,
            requestedBy:          $requestedBy,
            sourceLocationId:     $sourceLocationId,
            destinationLocationId: $destinationLocationId,
            status:               RequestStatus::PENDING,
            notes:                $notes,
            approvedBy:           null,
            approvedAt:           null,
            rejectedBy:           null,
            rejectedAt:           null,
            rejectionReason:      null,
            cancelledBy:          null,
            createdAt:            new DateTimeImmutable(),
            items:                $items,
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

    public function cancelledBy(): ?UserIdValueObject
    {
        return $this->cancelledBy;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function addItem(RequestItem $item): void
    {
        $this->items->add($item);
        $item->setRequest($this);
    }

    /** @return RequestItem[] */
    public function items(): array
    {
        return $this->items->toArray();
    }

    public function pendingItems(): array
    {
        return array_values(
            array_filter(
                $this->items->toArray(),
                static fn(RequestItem $item) => $item->isPending(),
            ),
        );
    }

    public function fulfilledItems(): array
    {
        return array_values(
            array_filter(
                $this->items->toArray(),
                static fn(RequestItem $item) => $item->isFulfilled(),
            ),
        );
    }

    public function allItemsCancelled(): bool
    {
        if ($this->items->isEmpty()) {
            return false;
        }

        foreach ($this->items as $item) {
            if (!$item->isCancelled()) {
                return false;
            }
        }

        return true;
    }

    public function allItemsFulfilled(): bool
    {
        if ($this->items->isEmpty()) {
            return false;
        }

        foreach ($this->items as $item) {
            if (!$item->isFulfilled()) {
                return false;
            }
        }

        return true;
    }

    public function derivedStatus(): RequestStatus
    {
        if ($this->allItemsCancelled()) {
            return RequestStatus::CANCELLED;
        }

        if ($this->allItemsFulfilled()) {
            return RequestStatus::FULFILLED;
        }

        foreach ($this->items as $item) {
            if ($item->isFulfilled()) {
                return RequestStatus::PARTIALLY_FULFILLED;
            }
        }

        return RequestStatus::PENDING;
    }

    public function fulfillItem(RequestItemIdValueObject $itemId, MovementIdValueObject $movementId): void
    {
        foreach ($this->items as $item) {
            if ((string) $item->id() === (string) $itemId) {
                $item->fulfill($movementId);
                break;
            }
        }

        $this->status = $this->derivedStatus();
    }

    public function cancelItem(RequestItemIdValueObject $itemId): RequestItem
    {
        $cancelled = null;

        foreach ($this->items as $item) {
            if ((string) $item->id() === (string) $itemId) {
                $item->cancel();
                $cancelled = $item;
                break;
            }
        }

        $this->status = $this->derivedStatus();

        return $cancelled;
    }

    public function setAllItemsPending(): void
    {
        foreach ($this->items as $item) {
            $item->setPending();
        }
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

        foreach ($this->items as $item) {
            if ($item->isPending()) {
                $item->fulfill($movementId);
            }
        }

        $this->status = $this->derivedStatus();
    }

    public function notifyTechnicianAcceptance(): void
    {
        if ($this->status !== RequestStatus::APPROVED) {
            throw new \LogicException('Solo se puede notificar al técnico desde estado APPROVED.');
        }

        $this->status = RequestStatus::AWAITING_TECHNICIAN_ACCEPTANCE;
    }

    public function acceptByTechnician(): void
    {
        if ($this->status !== RequestStatus::AWAITING_TECHNICIAN_ACCEPTANCE) {
            throw new \LogicException('Solo se puede aceptar desde estado AWAITING_TECHNICIAN_ACCEPTANCE.');
        }

        $this->status = RequestStatus::PARTIALLY_FULFILLED;
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

        foreach ($this->items as $item) {
            if ($item->isPending()) {
                $item->cancel();
            }
        }

        $this->status      = RequestStatus::CANCELLED;
        $this->cancelledBy = $cancelledBy;
    }
}
