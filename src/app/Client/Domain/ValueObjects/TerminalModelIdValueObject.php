<?php

declare(strict_types=1);

namespace App\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\TerminalModelIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class TerminalModelIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new TerminalModelIdException('Formato de ULID de modelo de terminal inválido.');
    }
}
