<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\CountUnreadNotifications;

final readonly class CountUnreadNotificationsQuery
{
    public function __construct(
        public string $employeeId,
    ) {}
}
