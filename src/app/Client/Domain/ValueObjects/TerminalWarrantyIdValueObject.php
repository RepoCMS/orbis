<?php

declare(strict_types=1);

namespace App\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\TerminalWarrantyIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class TerminalWarrantyIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new TerminalWarrantyIdException('Formato de ULID inválido para la garantía.');
    }
}
