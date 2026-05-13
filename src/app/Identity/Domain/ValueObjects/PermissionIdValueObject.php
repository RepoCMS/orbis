<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObjects;

use App\Identity\Domain\Exceptions\PermissionIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class PermissionIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new PermissionIdException('Formato de ULID inválido.');
    }
}
