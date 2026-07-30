<?php

declare(strict_types=1);

namespace App\Notification\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class NotificationPreferenceNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Notification preference con ID "%s" no encontrado.', $id));
    }
}
