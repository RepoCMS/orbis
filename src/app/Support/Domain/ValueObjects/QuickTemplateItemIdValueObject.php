<?php

declare(strict_types=1);

namespace App\Support\Domain\ValueObjects;

use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;
use App\Support\Domain\Exceptions\QuickTemplateItemIdException;

final readonly class QuickTemplateItemIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new QuickTemplateItemIdException('Formato de ID de item de plantilla rápida inválido.');
    }
}
