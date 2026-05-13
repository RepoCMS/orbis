<?php

declare(strict_types=1);

namespace App\Client\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class TerminalNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Terminal con ID "%s" no encontrado.', $id));
    }
}
