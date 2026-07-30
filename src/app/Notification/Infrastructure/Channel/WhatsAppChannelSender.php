<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Channel;

use App\Notification\Domain\Entities\NotificationLog;
use App\Notification\Domain\Services\ChannelSenderInterface;
use Psr\Log\LoggerInterface;

final readonly class WhatsAppChannelSender implements ChannelSenderInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function send(NotificationLog $log): void
    {
        $this->logger->info('[WhatsApp] Enviando WhatsApp', [
            'to' => $log->recipientId(),
            'body' => $log->body(),
            'notificationId' => (string) $log->id(),
        ]);
    }
}
