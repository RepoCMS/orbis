<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure\Doctrine\Types;

use App\Inventory\Domain\ValueObjects\PurchaseOrderIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class PurchaseOrderIdType extends AbstractUlidType
{
    public function getName(): string { return 'purchase_order_id'; }
    protected function getValueObjectClass(): string { return PurchaseOrderIdValueObject::class; }
}
