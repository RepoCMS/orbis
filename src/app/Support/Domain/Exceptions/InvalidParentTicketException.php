<?php

declare(strict_types=1);

namespace App\Support\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class InvalidParentTicketException extends DomainException
{
    public static function withMessage(string $message): self
    {
        return new self($message);
    }

    public static function invalidStatus(string $currentStatus): self
    {
        return new self(
            sprintf(
                'Solo se puede vincular un ticket padre en estado CREATED o ASSIGNED. Estado actual: %s.',
                $currentStatus,
            )
        );
    }
}
