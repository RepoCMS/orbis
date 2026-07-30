<?php

declare(strict_types=1);

namespace App\Support\Domain\ValueObjects;

use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;
use App\Support\Domain\Exceptions\ModuleNodeException;

final readonly class ModuleNodeIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new ModuleNodeException('Formato de ID de nodo de módulo inválido.');
    }
}
