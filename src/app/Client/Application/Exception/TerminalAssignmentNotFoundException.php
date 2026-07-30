<?php

declare(strict_types=1);

namespace App\Client\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class TerminalAssignmentNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Asignación de terminal con ID "%s" no encontrada.', $id));
    }
}
