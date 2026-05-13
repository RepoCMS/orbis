<?php

declare(strict_types=1);

namespace App\Client\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class SerialForTerminalAlreadyExistsException extends ApplicationException
{
    public static function withSerial(string $serial): self
    {
        return new self(sprintf('Ya existe un terminal con el número de serie "%s".', $serial));
    }
}
