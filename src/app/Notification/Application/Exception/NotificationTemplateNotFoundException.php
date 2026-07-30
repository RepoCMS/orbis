<?php

declare(strict_types=1);

namespace App\Notification\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class NotificationTemplateNotFoundException extends ApplicationException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Notification template con ID "%s" no encontrado.', $id));
    }
}
