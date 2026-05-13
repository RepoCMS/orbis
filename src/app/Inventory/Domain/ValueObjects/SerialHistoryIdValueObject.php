<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\SerialHistoryIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class SerialHistoryIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new SerialHistoryIdException('Formato de ID de historial de serial inválido.');
    }
}
