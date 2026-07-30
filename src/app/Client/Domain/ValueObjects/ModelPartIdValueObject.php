<?php

declare(strict_types=1);

namespace App\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\ModelPartIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class ModelPartIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new ModelPartIdException('Formato de ULID inválido.');
    }
}
