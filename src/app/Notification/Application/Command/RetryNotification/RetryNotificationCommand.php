<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\RetryNotification;

final readonly class RetryNotificationCommand
{
    public function __construct(
        public string $logId,
    ) {}
}
