<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\Response;

use App\Notification\Domain\Entities\NotificationLog;

final readonly class InboxNotificationResponse
{
    public function __construct(
        public string $id,
        public string $eventType,
        public string $title,
        public string $body,
        public ?string $referenceType,
        public ?string $referenceId,
        public ?string $readAt,
        public string $createdAt,
    ) {
    }

    public static function fromEntity(NotificationLog $log): self
    {
        return new self(
            id: (string) $log->id(),
            eventType: $log->eventType()->value,
            title: $log->subject() ?? $log->eventType()->label(),
            body: $log->body(),
            referenceType: $log->referenceType(),
            referenceId: $log->referenceId(),
            readAt: $log->readAt()?->format(\DateTimeInterface::ATOM),
            createdAt: $log->createdAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
