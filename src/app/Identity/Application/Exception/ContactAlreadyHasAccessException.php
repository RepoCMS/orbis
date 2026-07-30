<?php

declare(strict_types=1);

namespace App\Identity\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class ContactAlreadyHasAccessException extends ApplicationException
{
    public static function withId(string $contactId): self
    {
        return new self(sprintf('El contacto "%s" ya tiene acceso al portal.', $contactId));
    }
}
