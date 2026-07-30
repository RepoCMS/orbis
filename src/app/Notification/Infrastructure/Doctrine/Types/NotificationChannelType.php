<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Doctrine\Types;

use App\Notification\Domain\Enums\NotificationChannel;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class NotificationChannelType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'notification_channel';
    }

    protected function getEnumClass(): string
    {
        return NotificationChannel::class;
    }
}
