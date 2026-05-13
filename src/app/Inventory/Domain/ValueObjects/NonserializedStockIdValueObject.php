<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\NonserializedStockIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class NonserializedStockIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new NonserializedStockIdException('Formato de ID de stock no serializado inválido.');
    }
}
