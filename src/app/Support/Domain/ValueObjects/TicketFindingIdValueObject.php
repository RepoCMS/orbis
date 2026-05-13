<?php

declare(strict_types=1);

namespace App\Support\Domain\ValueObjects;

use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;
use App\Support\Domain\Exceptions\TicketFindingIdException;

final readonly class TicketFindingIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new TicketFindingIdException('Formato de ID de diagnóstico inválido.');
    }
}
