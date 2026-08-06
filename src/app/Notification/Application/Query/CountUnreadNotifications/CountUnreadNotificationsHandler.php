<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\CountUnreadNotifications;

use App\Notification\Domain\Repositories\NotificationLogRepositoryInterface;

final readonly class CountUnreadNotificationsHandler
{
    public function __construct(
        private NotificationLogRepositoryInterface $logRepository,
    ) {
    }

    public function __invoke(CountUnreadNotificationsQuery $query): int
    {
        return $this->logRepository->countUnread($query->employeeId);
    }
}
