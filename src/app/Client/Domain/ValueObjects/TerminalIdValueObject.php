<?php

declare(strict_types=1);

namespace App\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\TerminalIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class TerminalIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new TerminalIdException('Formato de ULID inválido.');
    }
}
