<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\CleanupOldNotifications;

use App\Notification\Domain\Repositories\NotificationLogRepositoryInterface;

final readonly class CleanupOldNotificationsHandler
{
    public function __construct(
        private NotificationLogRepositoryInterface $logRepository,
    ) {}

    public function __invoke(CleanupOldNotificationsCommand $command): int
    {
        return $this->logRepository->purgeOlderThan(new \DateTimeImmutable('-3 months'));
    }
}
