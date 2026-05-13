<?php

declare(strict_types=1);

namespace App\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\SiteIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class SiteIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new SiteIdException('Formato de ULID inválido.');
    }
}
