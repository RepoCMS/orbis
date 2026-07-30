<?php

declare(strict_types=1);

namespace App\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\ContractIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class ContractIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new ContractIdException('Formato de ULID inválido.');
    }
}
