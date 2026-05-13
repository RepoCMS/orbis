<?php

declare(strict_types=1);

namespace App\Inventory\Domain\ValueObjects;

use App\Inventory\Domain\Exceptions\TransformationIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class TransformationIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new TransformationIdException('Formato de ID de transformación inválido.');
    }
}
