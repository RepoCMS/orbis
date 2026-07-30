<?php

declare(strict_types=1);

namespace App\Support\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class TerminalModelNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Modelo de terminal con ID "%s" no encontrado.', $id));
    }
}
