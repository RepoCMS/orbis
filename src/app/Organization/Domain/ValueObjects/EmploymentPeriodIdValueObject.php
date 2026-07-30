<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObjects;

use App\Organization\Domain\Exceptions\EmploymentPeriodIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class EmploymentPeriodIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new EmploymentPeriodIdException('Formato de ULID inválido para EmploymentPeriodId.');
    }
}
