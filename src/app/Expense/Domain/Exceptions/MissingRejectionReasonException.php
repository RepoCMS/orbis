<?php

declare(strict_types=1);

namespace App\Expense\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class MissingRejectionReasonException extends DomainException
{
    public static function create(): self
    {
        return new self('La razón de rechazo es obligatoria para rechazar un item de gasto');
    }
}
