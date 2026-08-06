<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Inventory\Domain\Enums\RequestItemStatus;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestItemIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;

final class RequestItem
{
    private ?Request $request = null;

    private function __construct(
        private RequestItemIdValueObject $id,
        private ProductIdValueObject $productId,
        private ?SerializedStockIdValueObject $serializedStockId,
        private int $quantity,
        private RequestItemStatus $status,
        private ?MovementIdValueObject $fulfilledMovementId,
    ) {
    }

    public static function create(
        RequestItemIdValueObject $id,
        ProductIdValueObject $productId,
        ?SerializedStockIdValueObject $serializedStockId,
        int $quantity,
    ): self {
        return new self(
            id: $id,
            productId: $productId,
            serializedStockId: $serializedStockId,
            quantity: $quantity,
            status: RequestItemStatus::PENDING,
            fulfilledMovementId: null,
        );
    }

    public function fulfill(MovementIdValueObject $movementId): void
    {
        if ($this->status !== RequestItemStatus::PENDING) {
            throw new \LogicException('Solo se pueden despachar items en estado PENDING.');
        }

        $this->status = RequestItemStatus::FULFILLED;
        $this->fulfilledMovementId = $movementId;
    }

    public function setPending(): void
    {
        $this->status = RequestItemStatus::PENDING;
    }

    public function cancel(): void
    {
        if ($this->status !== RequestItemStatus::PENDING) {
            throw new \LogicException('Solo se pueden cancelar items en estado PENDING.');
        }

        $this->status = RequestItemStatus::CANCELLED;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    public function id(): RequestItemIdValueObject
    {
        return $this->id;
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

    public function status(): RequestItemStatus
    {
        return $this->status;
    }

    public function fulfilledMovementId(): ?MovementIdValueObject
    {
        return $this->fulfilledMovementId;
    }

    public function isPending(): bool
    {
        return $this->status === RequestItemStatus::PENDING;
    }

    public function isFulfilled(): bool
    {
        return $this->status === RequestItemStatus::FULFILLED;
    }

    public function isCancelled(): bool
    {
        return $this->status === RequestItemStatus::CANCELLED;
    }
}
