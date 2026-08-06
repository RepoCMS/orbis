<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\ListInboxNotifications;

use App\Notification\Application\Query\Response\InboxNotificationResponse;
use App\Notification\Domain\Repositories\NotificationLogRepositoryInterface;

final readonly class ListInboxNotificationsHandler
{
    public function __construct(
        private NotificationLogRepositoryInterface $logRepository,
    ) {
    }

    public function __invoke(ListInboxNotificationsQuery $query): array
    {
        $logs = $this->logRepository->findInbox(
            recipientId: $query->employeeId,
            unreadOnly: $query->unreadOnly,
        );

        return [
            'data' => array_map(
                static fn ($log) => InboxNotificationResponse::fromEntity($log),
                $logs,
            ),
            'unreadCount' => $this->logRepository->countUnread($query->employeeId),
        ];
    }
}
