<?php

declare(strict_types=1);

namespace App\Support\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class AssignmentNotFoundException extends DomainException
{
    public static function create(string $message): self
    {
        return new self($message);
    }

    public static function noTechnicianAssigned(): self
    {
        return new self('No se puede realizar esta operación sin una asignación de técnico.');
    }
}
