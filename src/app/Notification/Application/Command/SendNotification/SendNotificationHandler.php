<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\SendNotification;

use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Enums\RecipientType;
use App\Notification\Domain\Entities\NotificationLog;
use App\Notification\Domain\Repositories\NotificationLogRepositoryInterface;
use App\Notification\Domain\ValueObjects\NotificationLogIdValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class SendNotificationHandler
{
    public function __construct(
        private NotificationLogRepositoryInterface $logRepository,
        private IdGeneratorInterface $idGenerator,
        private MessageBusInterface $messageBus,
    ) {}

    public function __invoke(SendNotificationCommand $command): void
    {
        $id = new NotificationLogIdValueObject($this->idGenerator->generate());

        $log = NotificationLog::create(
            id: $id,
            recipientType: RecipientType::from($command->recipientType),
            recipientId: $command->recipientId,
            channel: NotificationChannel::from($command->channel),
            eventType: EventType::from($command->eventType),
            subject: $command->subject,
            body: $command->body,
            referenceType: $command->referenceType,
            referenceId: $command->referenceId,
        );

        $this->logRepository->save($log);

        $this->messageBus->dispatch(new SendNotificationMessage((string) $id));
    }
}
