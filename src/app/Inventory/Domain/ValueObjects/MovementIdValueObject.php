<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\MovementIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class MovementIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new MovementIdException('Formato de ID de movimiento inválido.');
    }
}
