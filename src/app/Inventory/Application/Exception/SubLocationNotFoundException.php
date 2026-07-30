<?php

declare(strict_types=1);

namespace App\Inventory\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class SubLocationNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Sub-ubicación con ID "%s" no encontrada.', $id));
    }
}
