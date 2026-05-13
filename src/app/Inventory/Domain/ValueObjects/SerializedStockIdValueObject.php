<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\SerializedStockIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class SerializedStockIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new SerializedStockIdException('Formato de ID de stock serializado inválido.');
    }
}
