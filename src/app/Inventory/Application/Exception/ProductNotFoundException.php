<?php

declare(strict_types=1);

namespace App\Inventory\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class ProductNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Producto con ID "%s" no encontrado.', $id));
    }
}
