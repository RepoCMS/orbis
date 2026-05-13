<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class EntryNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Entrada de inventario con ID "%s" no encontrada.', $id));
    }
}
