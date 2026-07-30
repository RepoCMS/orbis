<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\TokenException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class UserMfaBackupCodeIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new TokenException('El ID del código de respaldo MFA no es un ULID válido.');
    }
}
