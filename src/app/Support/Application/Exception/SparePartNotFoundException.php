<?php

declare(strict_types=1);

namespace App\Support\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class SparePartNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Repuesto con ID "%s" no encontrado.', $id));
    }
}
