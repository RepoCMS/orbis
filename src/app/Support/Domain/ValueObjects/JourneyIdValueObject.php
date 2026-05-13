<?php

declare(strict_types=1);

namespace App\Support\Domain\ValueObjects;

use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;
use App\Support\Domain\Exceptions\JourneyIdException;

final readonly class JourneyIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new JourneyIdException('Formato de ID de viaje inválido.');
    }
}
