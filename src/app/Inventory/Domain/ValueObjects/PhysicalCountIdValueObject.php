<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class PhysicalCountIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new InventoryDomainException('El ID de conteo físico no es un ULID válido.');
    }
}
