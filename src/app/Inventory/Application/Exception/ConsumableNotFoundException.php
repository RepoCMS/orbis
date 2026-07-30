<?php

declare(strict_types=1);

namespace App\Inventory\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class ConsumableNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Consumible con ID "%s" no encontrado.', $id));
    }
}
