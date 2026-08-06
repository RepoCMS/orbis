<?php

declare(strict_types=1);

namespace App\Organization\Domain\ValueObjects;

use App\Organization\Domain\Exceptions\EmployeeIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class EmployeeIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new EmployeeIdException('Formato de ULID inválido.');
    }

}
