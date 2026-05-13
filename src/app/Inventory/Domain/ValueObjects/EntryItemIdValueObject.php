<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\EntryItemIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class EntryItemIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new EntryItemIdException('Formato de ID de ítem de entrada inválido.');
    }
}
