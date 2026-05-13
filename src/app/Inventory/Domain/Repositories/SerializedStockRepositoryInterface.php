<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repositories;

use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;

interface SerializedStockRepositoryInterface
{
    public function findById(SerializedStockIdValueObject $id): ?SerializedStock;

    public function findBySerial(
        ProductIdValueObject $productId,
        StockSerialNumberValueObject $serialNumber,
    ): ?SerializedStock;

    /** @return SerializedStock[] */
    public function findByProduct(ProductIdValueObject $productId): array;

    /** @return SerializedStock[] Only AVAILABLE items */
    public function findAvailable(): array;

    /** @return SerializedStock[] */
    public function findByLocation(LocationIdValueObject $locationId): array;

    public function countAvailableByProductAndLocation(
        ProductIdValueObject $productId,
        LocationIdValueObject $locationId,
    ): int;

    public function save(SerializedStock $stock): void;

    public function delete(SerializedStockIdValueObject $id): void;
}
