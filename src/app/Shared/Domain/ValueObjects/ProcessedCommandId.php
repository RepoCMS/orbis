<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObjects;

use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class ProcessedCommandId extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new ProcessedCommandInvalidIdException();
    }
}
