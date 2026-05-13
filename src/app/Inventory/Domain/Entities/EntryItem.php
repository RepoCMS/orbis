<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Inventory\Domain\ValueObjects\EntryIdValueObject;
use App\Inventory\Domain\ValueObjects\EntryItemIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseOrderItemIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;

/**
 * Ítem individual dentro de una Entry.
 *
 * Representa la llegada de una unidad (serializada) o un lote (no serializada).
 *
 * - serialNumber = null  → stock no serializado; quantity > 0 es la cantidad.
 * - serialNumber != null → stock serializado; quantity = 1 siempre.
 * - purchaseOrderItemId puede ser null si la entrada es libre (sin orden previa).
 *
 * La ubicación de destino se registra aquí: es al almacén donde irá el stock.
 */
final class EntryItem
{
    public function __construct(
        private EntryItemIdValueObject $id,
        private EntryIdValueObject $entryId,
        private ProductIdValueObject $productId,
        private LocationIdValueObject $locationId,
        private ?PurchaseOrderItemIdValueObject $purchaseOrderItemId,
        private ?StockSerialNumberValueObject $serialNumber,
        private int $quantity,
    ) {
    }

    /**
     * Ítem serializado: siempre cantidad 1, con número de serie.
     */
    public static function createSerialized(
        EntryItemIdValueObject $id,
        EntryIdValueObject $entryId,
        ProductIdValueObject $productId,
        LocationIdValueObject $locationId,
        StockSerialNumberValueObject $serialNumber,
        ?PurchaseOrderItemIdValueObject $purchaseOrderItemId = null,
    ): self {
        return new self($id, $entryId, $productId, $locationId, $purchaseOrderItemId, $serialNumber, 1);
    }

    /**
     * Ítem no serializado: sin número de serie, con cantidad.
     */
    public static function createNonserialized(
        EntryItemIdValueObject $id,
        EntryIdValueObject $entryId,
        ProductIdValueObject $productId,
        LocationIdValueObject $locationId,
        int $quantity,
        ?PurchaseOrderItemIdValueObject $purchaseOrderItemId = null,
    ): self {
        return new self($id, $entryId, $productId, $locationId, $purchaseOrderItemId, null, $quantity);
    }

    public function id(): EntryItemIdValueObject
    {
        return $this->id;
    }

    public function entryId(): EntryIdValueObject
    {
        return $this->entryId;
    }

    public function productId(): ProductIdValueObject
    {
        return $this->productId;
    }

    public function locationId(): LocationIdValueObject
    {
        return $this->locationId;
    }

    public function purchaseOrderItemId(): ?PurchaseOrderItemIdValueObject
    {
        return $this->purchaseOrderItemId;
    }

    public function serialNumber(): ?StockSerialNumberValueObject
    {
        return $this->serialNumber;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function isSerialized(): bool
    {
        return $this->serialNumber !== null;
    }
}
