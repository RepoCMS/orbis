<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repositories;

use App\Inventory\Domain\Entities\Product;
use App\Inventory\Domain\ValueObjects\ProductCodeAssignedBySupplierValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;

interface ProductRepositoryInterface
{
    public function findById(ProductIdValueObject $id): ?Product;

    public function findByCode(ProductCodeAssignedBySupplierValueObject $code): ?Product;

    /** @return Product[] */
    public function findAll(): array;

    public function save(Product $product): void;

    public function delete(ProductIdValueObject $id): void;
}
