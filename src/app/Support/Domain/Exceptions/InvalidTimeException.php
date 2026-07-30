<?php

declare(strict_types=1);

namespace App\Support\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class InvalidTimeException extends DomainException
{
    public static function alreadyPerformed(string $action): self
    {
        return new self(
            sprintf('La operación "%s" ya fue realizada previamente.', $action)
        );
    }

    public static function message(string $message): self
    {
        return new self($message);
    }
}
