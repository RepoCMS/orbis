<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Inventory\Domain\Exceptions\InsufficientStockException;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseOrderItemIdValueObject;

/**
 * Línea de un producto dentro de una PurchaseOrder.
 * Registra qué se ordenó (quantityOrdered) y cuánto ha llegado (quantityReceived).
 */
final class PurchaseOrderItem
{
    public function __construct(
        private PurchaseOrderItemIdValueObject $id,
        private PurchaseOrderIdValueObject $purchaseOrderId,
        private ProductIdValueObject $productId,
        private int $quantityOrdered,
        private int $quantityReceived,
    ) {
    }

    public static function create(
        PurchaseOrderItemIdValueObject $id,
        PurchaseOrderIdValueObject $purchaseOrderId,
        ProductIdValueObject $productId,
        int $quantityOrdered,
    ): self {
        return new self($id, $purchaseOrderId, $productId, $quantityOrdered, 0);
    }

    public function id(): PurchaseOrderItemIdValueObject
    {
        return $this->id;
    }

    public function purchaseOrderId(): PurchaseOrderIdValueObject
    {
        return $this->purchaseOrderId;
    }

    public function productId(): ProductIdValueObject
    {
        return $this->productId;
    }

    public function quantityOrdered(): int
    {
        return $this->quantityOrdered;
    }

    public function quantityReceived(): int
    {
        return $this->quantityReceived;
    }

    public function quantityPending(): int
    {
        return $this->quantityOrdered - $this->quantityReceived;
    }

    public function isFullyReceived(): bool
    {
        return $this->quantityReceived >= $this->quantityOrdered;
    }

    /**
     * Registra la llegada de una cantidad de unidades para esta línea.
     * Lanza excepción si se intenta recibir más de lo pendiente.
     */
    public function receive(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('La cantidad a recibir debe ser mayor a cero.');
        }

        if ($quantity > $this->quantityPending()) {
            throw new InsufficientStockException(
                sprintf(
                    'Se intenta recibir %d unidades pero solo hay %d pendientes en la orden.',
                    $quantity,
                    $this->quantityPending()
                )
            );
        }

        $this->quantityReceived += $quantity;
    }
}
