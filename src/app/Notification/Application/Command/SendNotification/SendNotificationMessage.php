<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\SendNotification;

final readonly class SendNotificationMessage
{
    public function __construct(
        public string $notificationLogId,
    ) {}
}
