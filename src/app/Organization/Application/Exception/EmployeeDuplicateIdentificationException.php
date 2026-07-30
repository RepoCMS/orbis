<?php

declare(strict_types=1);

namespace App\Organization\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class EmployeeDuplicateIdentificationException extends ApplicationException
{
    public static function withIdentificationNumber(string $identificationNumber): self
    {
        return new self(sprintf(
            'El número de identificación "%s" ya está registrado.',
            $identificationNumber,
        ));
    }
}
