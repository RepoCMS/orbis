<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\MarkAllNotificationsRead;

use App\Notification\Domain\Repositories\NotificationLogRepositoryInterface;

final readonly class MarkAllNotificationsReadHandler
{
    public function __construct(
        private NotificationLogRepositoryInterface $logRepository,
    ) {
    }

    public function __invoke(MarkAllNotificationsReadCommand $command): void
    {
        foreach ($this->logRepository->findInbox($command->employeeId, unreadOnly: true) as $log) {
            $log->markAsRead();
            $this->logRepository->save($log);
        }
    }
}
