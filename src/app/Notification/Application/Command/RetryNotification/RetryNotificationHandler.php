<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\RetryNotification;

use App\Notification\Application\Exception\InvalidNotificationStatusException;
use App\Notification\Application\Exception\NotificationLogNotFoundException;
use App\Notification\Domain\Enums\NotificationStatus;
use App\Notification\Domain\Repositories\NotificationLogRepositoryInterface;
use App\Notification\Domain\ValueObjects\NotificationLogIdValueObject;

final readonly class RetryNotificationHandler
{
    public function __construct(
        private NotificationLogRepositoryInterface $logRepository,
    ) {}

    public function __invoke(RetryNotificationCommand $command): void
    {
        $id = NotificationLogIdValueObject::fromString($command->logId);

        $log = $this->logRepository->findById($id);

        if ($log === null) {
            throw NotificationLogNotFoundException::withId($command->logId);
        }

        if ($log->status() !== NotificationStatus::FAILED) {
            throw (new InvalidNotificationStatusException())->withContext([
                'logId' => $command->logId,
                'currentStatus' => $log->status()->value,
            ]);
        }

        $log->retry();

        $this->logRepository->save($log);
    }
}
