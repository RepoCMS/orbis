<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObjects;

use App\Organization\Domain\Exceptions\ServiceZoneIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class ServiceZoneIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new ServiceZoneIdException('Formato de ULID inválido para ServiceZoneId.');
    }
}
