<?php

declare(strict_types=1);

namespace App\Notification\Application\Command\SendNotification;

final readonly class SendNotificationCommand
{
    public function __construct(
        public string $recipientType,
        public string $recipientId,
        public string $channel,
        public string $eventType,
        public ?string $subject,
        public string $body,
        public ?string $referenceType = null,
        public ?string $referenceId = null,
    ) {}
}
