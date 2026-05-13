<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\RepairIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class RepairIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new RepairIdException('Formato de ID de reparación inválido.');
    }
}
