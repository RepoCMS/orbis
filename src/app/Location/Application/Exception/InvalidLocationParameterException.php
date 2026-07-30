<?php

declare(strict_types=1);

namespace App\Location\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class InvalidLocationParameterException extends ApplicationException
{
    public static function invalidUlid(string $paramName): self
    {
        return new self(sprintf('El parámetro "%s" debe ser un ULID válido de 26 caracteres.', $paramName));
    }

    public static function shortSearchTerm(int $minChars): self
    {
        return new self(sprintf('El término de búsqueda debe tener al menos %d caracteres.', $minChars));
    }
}
