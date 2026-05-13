<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\StockMinimumIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class StockMinimumIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new StockMinimumIdException('Formato de ID de stock mínimo inválido.');
    }
}
