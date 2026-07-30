<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\ListInboxNotifications;

final readonly class ListInboxNotificationsQuery
{
    public function __construct(
        public string $employeeId,
        public bool $unreadOnly = false,
        public int $page = 1,
        public int $limit = 20,
    ) {}
}
