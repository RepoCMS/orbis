<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\AuthLogException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class AuthLogIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new AuthLogException('El ID del registro de auditoría no es un ULID válido.');
    }
}
