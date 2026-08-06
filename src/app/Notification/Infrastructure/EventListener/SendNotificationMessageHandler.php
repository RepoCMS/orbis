<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\EventListener;

use App\Notification\Application\Command\SendNotification\SendNotificationMessage;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Repositories\NotificationLogRepositoryInterface;
use App\Notification\Domain\Services\ChannelSenderInterface;
use App\Notification\Domain\ValueObjects\NotificationLogIdValueObject;
use App\Notification\Infrastructure\Channel\EmailChannelSender;
use App\Notification\Infrastructure\Channel\InAppChannelSender;
use App\Notification\Infrastructure\Channel\SmsChannelSender;
use App\Notification\Infrastructure\Channel\WhatsAppChannelSender;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendNotificationMessageHandler
{
    /** @var array<string, ChannelSenderInterface> */
    private array $senders;

    public function __construct(
        private NotificationLogRepositoryInterface $logRepository,
        InAppChannelSender $inAppSender,
        EmailChannelSender $emailSender,
        SmsChannelSender $smsSender,
        WhatsAppChannelSender $whatsappSender,
    ) {
        $this->senders = [
            NotificationChannel::IN_APP->value => $inAppSender,
            NotificationChannel::EMAIL->value => $emailSender,
            NotificationChannel::SMS->value => $smsSender,
            NotificationChannel::WHATSAPP->value => $whatsappSender,
        ];
    }

    public function __invoke(SendNotificationMessage $message): void
    {
        $id = NotificationLogIdValueObject::fromString($message->notificationLogId);
        $log = $this->logRepository->findById($id);

        if ($log === null || !$log->canRetry()) {
            return;
        }

        $sender = $this->senders[$log->channel()->value] ?? null;

        if ($sender === null) {
            $log->markFailed(sprintf('No hay sender configurado para el canal: %s', $log->channel()->value));
            $this->logRepository->save($log);

            return;
        }

        try {
            $sender->send($log);
            $log->markSent();
        } catch (\Throwable $e) {
            $log->markFailed($e->getMessage());
        }

        $this->logRepository->save($log);
    }
}
