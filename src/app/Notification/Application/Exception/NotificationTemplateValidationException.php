<?php

declare(strict_types=1);

namespace App\Notification\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class NotificationTemplateValidationException extends ApplicationException
{
    public static function emptyBody(): self
    {
        return new self('El cuerpo de la plantilla no puede estar vacío.');
    }
}
