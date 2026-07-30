<?php

declare(strict_types=1);

namespace App\Support\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class InvalidScheduleException extends DomainException
{
    public static function futureDateRequired(): self
    {
        return new self('La nueva fecha debe ser en el futuro.');
    }

    public static function message(string $message): self
    {
        return new self($message);
    }
}
