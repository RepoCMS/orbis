<?php

declare(strict_types=1);

namespace App\Inventory\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class ProductCodeAlreadyExistsException extends ApplicationException
{
    public static function withCode(string $code): self
    {
        return new self(sprintf('El código de producto "%s" ya está en uso.', $code));
    }
}
