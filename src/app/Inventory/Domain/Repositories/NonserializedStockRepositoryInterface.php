<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repositories;

use App\Inventory\Domain\Entities\NonserializedStock;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\NonserializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;

interface NonserializedStockRepositoryInterface
{
    public function findById(NonserializedStockIdValueObject $id): ?NonserializedStock;

    public function findByProductAndLocation(
        ProductIdValueObject $productId,
        LocationIdValueObject $locationId,
    ): ?NonserializedStock;

    public function countAvailableByProductAndLocation(
        ProductIdValueObject $productId,
        LocationIdValueObject $locationId,
    ): int;

    /** @return NonserializedStock[] */
    public function findByProduct(ProductIdValueObject $productId): array;

    /** @return NonserializedStock[] */
    public function findByLocation(LocationIdValueObject $locationId): array;

    public function save(NonserializedStock $stock): void;

    public function delete(NonserializedStockIdValueObject $id): void;
}
