<?php

declare(strict_types=1);

namespace App\Location\Domain\ValueObjects;

use App\Location\Domain\Exceptions\RegionIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class RegionIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new RegionIdException('Formato de ULID invalido para RegionId.');
    }
}