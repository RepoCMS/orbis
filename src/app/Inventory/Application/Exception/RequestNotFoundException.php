<?php

declare(strict_types=1);

namespace App\Inventory\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class RequestNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Solicitud con ID "%s" no encontrada.', $id));
    }
}
