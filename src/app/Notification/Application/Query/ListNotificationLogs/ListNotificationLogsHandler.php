<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\ListNotificationLogs;

use App\Notification\Application\Query\Response\NotificationLogResponse;
use App\Notification\Domain\Repositories\NotificationLogRepositoryInterface;

final readonly class ListNotificationLogsHandler
{
    public function __construct(
        private NotificationLogRepositoryInterface $logRepository,
    ) {}

    public function __invoke(ListNotificationLogsQuery $query): array
    {
        $logs = $this->logRepository->findAll(
            status: $query->status,
            recipientType: $query->recipientType,
            recipientId: $query->recipientId,
            eventType: $query->eventType,
            dateFrom: $query->dateFrom,
            dateTo: $query->dateTo,
        );

        return array_map(
            static fn ($log) => NotificationLogResponse::fromEntity($log),
            $logs,
        );
    }
}
