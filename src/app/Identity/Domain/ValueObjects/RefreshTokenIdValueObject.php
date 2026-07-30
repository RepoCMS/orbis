<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\RefreshTokenException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class RefreshTokenIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new RefreshTokenException('El ID del refresh token no es un ULID válido.');
    }
}
