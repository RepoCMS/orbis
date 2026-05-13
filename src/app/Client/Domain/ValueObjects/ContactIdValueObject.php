<?php

declare(strict_types=1);

namespace App\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\ContactIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class ContactIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new ContactIdException('Formato de ULID inválido.');
    }
}
