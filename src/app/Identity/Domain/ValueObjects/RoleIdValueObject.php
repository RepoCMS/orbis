<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\RoleIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class RoleIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new RoleIdException('Formato de ULID inválido.');
    }
}
