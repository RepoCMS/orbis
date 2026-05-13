<?php

declare(strict_types=1);

namespace App\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\TerminalAssignmentIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class TerminalAssignmentIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new TerminalAssignmentIdException('Formato de ULID inválido.');
    }
}
