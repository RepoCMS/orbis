<?php

declare(strict_types=1);

namespace App\Support\Domain\ValueObjects;

use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;
use App\Support\Domain\Exceptions\TicketEvidenceIdException;

final readonly class TicketEvidenceIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new TicketEvidenceIdException('Formato de ID de evidencia inválido.');
    }
}
