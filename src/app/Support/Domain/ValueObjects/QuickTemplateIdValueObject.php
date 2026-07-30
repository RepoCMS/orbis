<?php

declare(strict_types=1);

namespace App\Support\Domain\ValueObjects;

use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;
use App\Support\Domain\Exceptions\QuickTemplateIdException;

final readonly class QuickTemplateIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new QuickTemplateIdException('Formato de ID de plantilla rápida inválido.');
    }
}
