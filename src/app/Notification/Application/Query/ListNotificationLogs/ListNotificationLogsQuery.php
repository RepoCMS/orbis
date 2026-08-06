<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\ListNotificationLogs;

final readonly class ListNotificationLogsQuery
{
    public function __construct(
        public ?string $status = null,
        public ?string $recipientType = null,
        public ?string $recipientId = null,
        public ?string $eventType = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public int $page = 1,
        public int $limit = 20,
    ) {
    }
}
