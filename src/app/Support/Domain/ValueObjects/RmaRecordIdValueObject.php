<?php

declare(strict_types=1);

namespace App\Support\Domain\ValueObjects;

use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;
use App\Support\Domain\Exceptions\RmaRecordIdException;

final readonly class RmaRecordIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new RmaRecordIdException('Formato de ID de registro RMA inválido.');
    }
}
