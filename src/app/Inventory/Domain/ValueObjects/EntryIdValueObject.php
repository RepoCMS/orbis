<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\EntryIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class EntryIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new EntryIdException('Formato de ID de entrada de inventario inválido.');
    }
}
