<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObjects;

use App\Organization\Domain\Exceptions\VacationRequestIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class VacationRequestIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new VacationRequestIdException('Formato de ULID inválido para VacationRequestId.');
    }
}
