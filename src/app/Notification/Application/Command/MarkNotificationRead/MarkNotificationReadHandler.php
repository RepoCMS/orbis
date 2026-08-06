<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\MarkNotificationRead;

use App\Notification\Application\Exception\NotificationAccessDeniedException;
use App\Notification\Application\Exception\NotificationLogNotFoundException;
use App\Notification\Domain\Enums\RecipientType;
use App\Notification\Domain\Repositories\NotificationLogRepositoryInterface;
use App\Notification\Domain\ValueObjects\NotificationLogIdValueObject;

final readonly class MarkNotificationReadHandler
{
    public function __construct(
        private NotificationLogRepositoryInterface $logRepository,
    ) {
    }

    public function __invoke(MarkNotificationReadCommand $command): void
    {
        $id = NotificationLogIdValueObject::fromString($command->logId);

        $log = $this->logRepository->findById($id);

        if ($log === null) {
            throw NotificationLogNotFoundException::withId($command->logId);
        }

        if ($log->recipientType() !== RecipientType::EMPLOYEE || $log->recipientId() !== $command->employeeId) {
            throw NotificationAccessDeniedException::notOwner($command->logId, $command->employeeId);
        }

        $log->markAsRead();

        $this->logRepository->save($log);
    }
}
