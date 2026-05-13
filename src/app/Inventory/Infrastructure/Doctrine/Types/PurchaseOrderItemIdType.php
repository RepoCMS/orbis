<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\PurchaseOrderItemIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class PurchaseOrderItemIdType extends AbstractUlidType
{
    public function getName(): string { return 'purchase_order_item_id'; }
    protected function getValueObjectClass(): string { return PurchaseOrderItemIdValueObject::class; }
}
