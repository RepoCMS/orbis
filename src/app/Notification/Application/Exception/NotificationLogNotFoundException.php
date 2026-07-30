<?php

declare(strict_types=1);

namespace App\Notification\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class NotificationLogNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Notification log con ID "%s" no encontrado.', $id));
    }
}
