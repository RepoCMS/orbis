<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\ProductIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class ProductIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new ProductIdException('Formato de ID de producto inválido.');
    }
}
