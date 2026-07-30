<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\InventoryDomainException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class SubLocationIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new InventoryDomainException('El ID de sub-ubicación no es un ULID válido.');
    }
}
