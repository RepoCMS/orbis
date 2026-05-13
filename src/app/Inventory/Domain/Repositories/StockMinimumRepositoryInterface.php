<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repositories;

use App\Inventory\Domain\Entities\StockMinimum;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\StockMinimumIdValueObject;

interface StockMinimumRepositoryInterface
{
    public function findById(StockMinimumIdValueObject $id): ?StockMinimum;

    public function findByProductAndLocation(
        ProductIdValueObject $productId,
        LocationIdValueObject $locationId,
    ): ?StockMinimum;

    /** @return StockMinimum[] */
    public function findByLocation(LocationIdValueObject $locationId): array;

    /** @return StockMinimum[] */
    public function findAll(): array;

    public function save(StockMinimum $stockMinimum): void;

    public function delete(StockMinimumIdValueObject $id): void;
}
