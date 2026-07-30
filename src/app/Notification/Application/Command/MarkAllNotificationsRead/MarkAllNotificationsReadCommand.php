<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\MarkAllNotificationsRead;

final readonly class MarkAllNotificationsReadCommand
{
    public function __construct(
        public string $employeeId,
    ) {}
}
