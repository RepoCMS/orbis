<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObjects;

use App\Organization\Domain\Exceptions\EmployeeSpecialPeriodIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class EmployeeSpecialPeriodIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new EmployeeSpecialPeriodIdException('Formato de ULID inválido para EmployeeSpecialPeriodId.');
    }
}
