<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Entities;

use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\StockMinimumIdValueObject;

/**
 * Umbral de stock mínimo para un producto en una ubicación concreta.
 *
 * Cuando el stock disponible cae por debajo de `alertThreshold`,
 * el dashboard del coordinador muestra una alerta.
 */
final class StockMinimum
{
    public function __construct(
        private StockMinimumIdValueObject $id,
        private ProductIdValueObject $productId,
        private LocationIdValueObject $locationId,
        private int $idealQuantity,
        private int $alertThreshold,
    ) {
    }

    public static function define(
        StockMinimumIdValueObject $id,
        ProductIdValueObject $productId,
        LocationIdValueObject $locationId,
        int $idealQuantity,
        int $alertThreshold,
    ): self {
        if ($idealQuantity < 0) {
            throw new \InvalidArgumentException('La cantidad ideal no puede ser negativa.');
        }

        if ($alertThreshold < 0) {
            throw new \InvalidArgumentException('El umbral de alerta no puede ser negativo.');
        }

        if ($alertThreshold > $idealQuantity) {
            throw new \InvalidArgumentException('El umbral de alerta no puede superar la cantidad ideal.');
        }

        return new self($id, $productId, $locationId, $idealQuantity, $alertThreshold);
    }

    public function id(): StockMinimumIdValueObject
    {
        return $this->id;
    }

    public function productId(): ProductIdValueObject
    {
        return $this->productId;
    }

    public function locationId(): LocationIdValueObject
    {
        return $this->locationId;
    }

    public function idealQuantity(): int
    {
        return $this->idealQuantity;
    }

    public function alertThreshold(): int
    {
        return $this->alertThreshold;
    }

    public function update(int $idealQuantity, int $alertThreshold): void
    {
        if ($idealQuantity < 0) {
            throw new \InvalidArgumentException('La cantidad ideal no puede ser negativa.');
        }

        if ($alertThreshold < 0) {
            throw new \InvalidArgumentException('El umbral de alerta no puede ser negativo.');
        }

        if ($alertThreshold > $idealQuantity) {
            throw new \InvalidArgumentException('El umbral de alerta no puede superar la cantidad ideal.');
        }

        $this->idealQuantity  = $idealQuantity;
        $this->alertThreshold = $alertThreshold;
    }

    public function isBelowThreshold(int $currentStock): bool
    {
        return $currentStock <= $this->alertThreshold;
    }
}
