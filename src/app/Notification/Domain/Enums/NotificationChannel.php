<?php

declare(strict_types=1);

namespace App\Notification\Domain\Enums;

enum NotificationChannel: string
{
    case IN_APP = 'in_app';
    case EMAIL = 'email';
    case WHATSAPP = 'whatsapp';
    case SMS = 'sms';

    public function label(): string
    {
        return match ($this) {
            self::IN_APP => 'In-App',
            self::EMAIL => 'Correo electrónico',
            self::WHATSAPP => 'WhatsApp',
            self::SMS => 'SMS',
        };
    }

    public function isMvp(): bool
    {
        return $this === self::IN_APP || $this === self::EMAIL;
    }
}
