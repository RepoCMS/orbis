<?php

declare(strict_types=1);

namespace App\Identity\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class ContactNotFoundException extends ApplicationException
{
    public static function withId(string $contactId): self
    {
        return new self(sprintf('No se encontró el contacto con ID "%s".', $contactId));
    }
}
