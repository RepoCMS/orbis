<?php

declare(strict_types=1);

namespace App\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\ClientIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class ClientIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new ClientIdException('Formato de ULID inválido.');
    }
}
