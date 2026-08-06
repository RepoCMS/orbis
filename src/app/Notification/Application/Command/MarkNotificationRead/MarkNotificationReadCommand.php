<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\MarkNotificationRead;

final readonly class MarkNotificationReadCommand
{
    public function __construct(
        public string $logId,
        public string $employeeId,
    ) {
    }
}
