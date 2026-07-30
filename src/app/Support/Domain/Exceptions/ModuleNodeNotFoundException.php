<?php

declare(strict_types=1);

namespace App\Support\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class ModuleNodeNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Módulo del árbol con ID "%s" no encontrado.', $id));
    }

    public static function withCode(string $code): self
    {
        return new self(sprintf('Módulo del árbol con código "%s" no encontrado.', $code));
    }
}
