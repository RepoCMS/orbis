<?php

declare(strict_types=1);

namespace App\Inventory\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class RepairNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Reparación con ID "%s" no encontrada.', $id));
    }
}
