<?php

declare(strict_types=1);

namespace App\Support\Domain\ValueObjects;

use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;
use App\Support\Domain\Exceptions\ScheduledMaintenanceIdException;

final readonly class ScheduledMaintenanceIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new ScheduledMaintenanceIdException('Formato de ID de mantenimiento programado inválido.');
    }
}
