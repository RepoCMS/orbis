<?php

declare(strict_types=1);

namespace App\Support\Domain\ValueObjects;

use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;
use App\Support\Domain\Exceptions\FindingComponentIdException;

final readonly class FindingComponentIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new FindingComponentIdException('Formato de ID de componente de diagnóstico inválido.');
    }
}
