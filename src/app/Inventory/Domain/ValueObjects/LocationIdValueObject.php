<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\LocationIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class LocationIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new LocationIdException('Formato de ID de ubicación inválido.');
    }
}
