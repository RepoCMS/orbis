<?php

declare(strict_types=1);

namespace App\Location\Domain\ValueObjects;

use App\Location\Domain\Exceptions\CityIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class CityIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new CityIdException('Formato de ULID invalido para CityId.');
    }
}
