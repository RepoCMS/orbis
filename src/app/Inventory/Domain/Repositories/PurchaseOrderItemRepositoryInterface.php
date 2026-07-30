<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Repositories;

use App\Inventory\Domain\Entities\PurchaseOrderItem;
use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;
use App\Inventory\Domain\ValueObjects\PurchaseOrderItemIdValueObject;

interface PurchaseOrderItemRepositoryInterface
{
    public function findById(PurchaseOrderItemIdValueObject $id): ?PurchaseOrderItem;

    /** @return PurchaseOrderItem[] */
    public function findByPurchaseOrder(PurchaseOrderIdValueObject $purchaseOrderId): array;

    public function save(PurchaseOrderItem $item): void;

    public function findLastPurchaseDateByProduct(string $productId): ?\DateTimeImmutable;
}
