<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\PurchaseOrderItemIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class PurchaseOrderItemIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new PurchaseOrderItemIdException('Formato de ID de ítem de orden de compra inválido.');
    }
}
