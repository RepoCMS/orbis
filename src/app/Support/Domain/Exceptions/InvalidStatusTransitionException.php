<?php

declare(strict_types=1);

namespace App\Support\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class InvalidStatusTransitionException extends DomainException
{
    public static function forTransition(string $from, string $to): self
    {
        return new self(
            sprintf(
                'No se puede cambiar el estado de "%s" a "%s". Estado actual no válido para esta transición.',
                $from,
                $to,
            )
        );
    }

    public static function withMessage(string $message): self
    {
        return new self($message);
    }
}
