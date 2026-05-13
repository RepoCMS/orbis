<?php

declare(strict_types=1);

namespace App\Support\Domain\ValueObjects;

use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;
use App\Support\Domain\Exceptions\TicketAssignmentIdException;

final readonly class TicketAssignmentIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new TicketAssignmentIdException('Formato de ID de asignación de ticket inválido.');
    }
}
