<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\UserIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class UserIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new UserIdException('Formato de ULID inválido.');
    }
}
