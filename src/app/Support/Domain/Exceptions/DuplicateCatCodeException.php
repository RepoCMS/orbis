<?php

declare(strict_types=1);

namespace App\Support\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class DuplicateCatCodeException extends DomainException
{
    public static function withCode(string $catCode): self
    {
        return new self(
            sprintf('El código CAT "%s" ya existe.', $catCode)
        );
    }
}
