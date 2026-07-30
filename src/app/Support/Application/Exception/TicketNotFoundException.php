<?php

declare(strict_types=1);

namespace App\Support\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class TicketNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Ticket con ID "%s" no encontrado.', $id));
    }
}
