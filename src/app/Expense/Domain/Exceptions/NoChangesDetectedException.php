<?php

declare(strict_types=1);

namespace App\Expense\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class NoChangesDetectedException extends DomainException
{
    public static function create(): self
    {
        return new self('No se detectaron cambios en la operación solicitada');
    }
}
