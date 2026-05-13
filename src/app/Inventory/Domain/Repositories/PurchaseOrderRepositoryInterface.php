<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repositories;

use App\Inventory\Domain\Entities\PurchaseOrder;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;

interface PurchaseOrderRepositoryInterface
{
    public function findById(PurchaseOrderIdValueObject $id): ?PurchaseOrder;

    public function findByReference(string $reference): ?PurchaseOrder;

    /** @return PurchaseOrder[] */
    public function findOpen(): array;

    /** @return PurchaseOrder[] */
    public function findAll(): array;

    public function save(PurchaseOrder $purchaseOrder): void;
}
