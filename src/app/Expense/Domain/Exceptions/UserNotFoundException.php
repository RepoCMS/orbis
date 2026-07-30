<?php

declare(strict_types=1);

namespace App\Expense\Domain\Exceptions;

use App\Shared\Exceptions\DomainException;

final class UserNotFoundException extends DomainException
{
    public function __construct(string $userId)
    {
        parent::__construct(
            sprintf('Usuario no encontrado: %s', $userId),
        );
    }
}
