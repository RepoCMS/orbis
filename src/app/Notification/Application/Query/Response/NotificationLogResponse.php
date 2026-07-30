<?php

declare(strict_types=1);

namespace App\Notification\Application\Query\Response;

use App\Notification\Domain\Entities\NotificationLog;

final readonly class NotificationLogResponse
{
    public function __construct(
        public string $id,
        public string $recipientType,
        public string $recipientId,
        public string $channel,
        public string $eventType,
        public ?string $subject,
        public string $body,
        public string $status,
        public int $attemptCount,
        public ?string $sentAt,
        public ?string $failureReason,
        public ?string $referenceType,
        public ?string $referenceId,
        public ?string $readAt,
        public string $createdAt,
    ) {}

    public static function fromEntity(NotificationLog $log): self
    {
        return new self(
            id: (string) $log->id(),
            recipientType: $log->recipientType()->value,
            recipientId: $log->recipientId(),
            channel: $log->channel()->value,
            eventType: $log->eventType()->value,
            subject: $log->subject(),
            body: $log->body(),
            status: $log->status()->value,
            attemptCount: $log->attemptCount(),
            sentAt: $log->sentAt()?->format(\DateTimeInterface::ATOM),
            failureReason: $log->failureReason(),
            referenceType: $log->referenceType(),
            referenceId: $log->referenceId(),
            readAt: $log->readAt()?->format(\DateTimeInterface::ATOM),
            createdAt: $log->createdAt()->format(\DateTimeInterface::ATOM),
        );
    }
}
