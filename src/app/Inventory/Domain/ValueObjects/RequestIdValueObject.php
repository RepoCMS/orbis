<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\RequestIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class RequestIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new RequestIdException('Formato de ID de solicitud inválido.');
    }
}
