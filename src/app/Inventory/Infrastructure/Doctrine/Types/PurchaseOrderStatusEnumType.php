<?php
declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;
use App\Inventory\Domain\Enums\PurchaseOrderStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class PurchaseOrderStatusEnumType extends AbstractStringEnumType
{
    public function getName(): string { return 'inventory_purchase_order_status'; }
    protected function getEnumClass(): string { return PurchaseOrderStatus::class; }
}
