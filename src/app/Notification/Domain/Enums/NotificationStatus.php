<?php

declare(strict_types=1);

namespace App\Notification\Domain\Enums;

enum NotificationStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::SENT => 'Enviado',
            self::FAILED => 'Fallido',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::SENT || $this === self::FAILED;
    }
}
