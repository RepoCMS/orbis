<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repositories;

use App\Inventory\Domain\Entities\Transformation;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\TransformationIdValueObject;

interface TransformationRepositoryInterface
{
    public function findById(TransformationIdValueObject $id): ?Transformation;

    /** @return Transformation[] */
    public function findAll(): array;

    /** @return Transformation[] */
    public function findBySerializedStock(SerializedStockIdValueObject $stockId): array;

    public function save(Transformation $transformation): void;
}
