<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObjects;

use App\Organization\Domain\Exceptions\ServiceZoneAssignmentIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class ServiceZoneAssignmentIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new ServiceZoneAssignmentIdException('Formato de ULID inválido para ServiceZoneAssignmentId.');
    }
}
