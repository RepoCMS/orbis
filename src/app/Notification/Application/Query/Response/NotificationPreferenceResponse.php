<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\Response;

use App\Notification\Domain\Entities\NotificationPreference;

final readonly class NotificationPreferenceResponse
{
    public function __construct(
        public string $id,
        public string $recipientType,
        public string $recipientId,
        public string $channel,
        public string $eventType,
        public bool $isEnabled,
    ) {
    }

    public static function fromEntity(NotificationPreference $preference): self
    {
        return new self(
            id: (string) $preference->id(),
            recipientType: $preference->recipientType()->value,
            recipientId: $preference->recipientId(),
            channel: $preference->channel()->value,
            eventType: $preference->eventType()->value,
            isEnabled: $preference->isEnabled(),
        );
    }
}
