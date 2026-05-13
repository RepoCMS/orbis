<?php

declare(strict_types=1);

namespace App\Client\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class ClientRucAlreadyExistsException extends ApplicationException
{
    public static function withRuc(string $ruc): self
    {
        return new self(sprintf('El RUC "%s" ya está registrado.', $ruc));
    }
}
