<?php

declare(strict_types=1);

namespace App\Notification\Application\Exception;

use App\Shared\Exceptions\ApplicationException;

final class NotificationAccessDeniedException extends ApplicationException
{
    public static function notOwner(string $logId, string $employeeId): self
    {
        return new self(sprintf(
            'La notificación "%s" no pertenece al empleado "%s".',
            $logId,
            $employeeId,
        ));
    }
}
