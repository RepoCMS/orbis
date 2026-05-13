<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repositories;

use App\Inventory\Domain\Entities\Movement;
use App\Inventory\Domain\Enums\MovementStatus;
use App\Inventory\Domain\ValueObjects\MovementIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;

interface MovementRepositoryInterface
{
    public function findById(MovementIdValueObject $id): ?Movement;

    /** @return Movement[] */
    public function findAll(): array;

    /** @return Movement[] */
    public function findByProduct(ProductIdValueObject $productId): array;

    /** @return Movement[] */
    public function findByStatus(MovementStatus $status): array;

    public function save(Movement $movement): void;
}
