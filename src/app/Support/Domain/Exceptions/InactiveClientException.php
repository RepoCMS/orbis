<?php

declare(strict_types=1);

namespace App\Support\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class InactiveClientException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(
            sprintf('El cliente con ID "%s" no está activo.', $id)
        );
    }
}
