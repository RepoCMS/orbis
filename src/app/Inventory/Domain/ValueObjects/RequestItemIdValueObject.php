<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\RequestItemIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class RequestItemIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new RequestItemIdException('Formato de ID de item de solicitud inválido.');
    }
}
