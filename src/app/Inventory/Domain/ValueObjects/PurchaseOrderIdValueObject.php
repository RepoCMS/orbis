<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\PurchaseOrderIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class PurchaseOrderIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new PurchaseOrderIdException('Formato de ID de orden de compra inválido.');
    }
}
