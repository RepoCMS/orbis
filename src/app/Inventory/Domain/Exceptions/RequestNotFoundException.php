<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class RequestNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Solicitud con ID "%s" no encontrada.', $id));
    }
}
