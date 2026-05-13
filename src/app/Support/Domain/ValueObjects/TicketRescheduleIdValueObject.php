<?php

declare(strict_types=1);

namespace App\Support\Domain\ValueObjects;

use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;
use App\Support\Domain\Exceptions\TicketRescheduleIdException;

final readonly class TicketRescheduleIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new TicketRescheduleIdException('Formato de ID de reprogramación inválido.');
    }
}
