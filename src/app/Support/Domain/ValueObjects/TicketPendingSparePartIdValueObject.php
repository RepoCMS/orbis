<?php

declare(strict_types=1);

namespace App\Support\Domain\ValueObjects;

use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;
use App\Support\Domain\Exceptions\TicketPendingSparePartIdException;

final readonly class TicketPendingSparePartIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new TicketPendingSparePartIdException('Formato de ID de repuesto pendiente inválido.');
    }
}
