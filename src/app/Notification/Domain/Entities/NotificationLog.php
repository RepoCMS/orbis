<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entities;

use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Enums\NotificationStatus;
use App\Notification\Domain\Enums\RecipientType;
use App\Notification\Domain\Exceptions\CannotMarkReadNonInAppException;
use App\Notification\Domain\Exceptions\EmptyBodyException;
use App\Notification\Domain\Exceptions\EmptyRecipientException;
use App\Notification\Domain\Exceptions\InvalidNotificationStatusTransitionException;
use App\Notification\Domain\ValueObjects\NotificationLogIdValueObject;
use App\Shared\Domain\Contracts\TimestampableInterface;
use DateTimeImmutable;

final class NotificationLog implements TimestampableInterface
{
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt = null;

    private NotificationStatus $status;
    private int $attemptCount = 0;
    private ?DateTimeImmutable $lastAttemptAt = null;
    private ?DateTimeImmutable $sentAt = null;
    private ?string $failureReason = null;
    private ?DateTimeImmutable $readAt = null;

    private function __construct(
        private NotificationLogIdValueObject $id,
        private RecipientType $recipientType,
        private string $recipientId,
        private NotificationChannel $channel,
        private EventType $eventType,
        private ?string $subject,
        private string $body,
        private ?string $referenceType = null,
        private ?string $referenceId = null,
    ) {
        if (trim($recipientId) === '') {
            throw new EmptyRecipientException('El destinatario es obligatorio');
        }
        if (trim($body) === '') {
            throw new EmptyBodyException('El cuerpo de la notificación es obligatorio');
        }

        $this->status = NotificationStatus::PENDING;
        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(
        NotificationLogIdValueObject $id,
        RecipientType $recipientType,
        string $recipientId,
        NotificationChannel $channel,
        EventType $eventType,
        ?string $subject,
        string $body,
        ?string $referenceType = null,
        ?string $referenceId = null,
    ): self {
        return new self(
            $id,
            $recipientType,
            $recipientId,
            $channel,
            $eventType,
            $subject,
            $body,
            $referenceType,
            $referenceId,
        );
    }

    public function id(): NotificationLogIdValueObject
    {
        return $this->id;
    }

    public function recipientType(): RecipientType
    {
        return $this->recipientType;
    }

    public function recipientId(): string
    {
        return $this->recipientId;
    }

    public function channel(): NotificationChannel
    {
        return $this->channel;
    }

    public function eventType(): EventType
    {
        return $this->eventType;
    }

    public function subject(): ?string
    {
        return $this->subject;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function status(): NotificationStatus
    {
        return $this->status;
    }

    public function attemptCount(): int
    {
        return $this->attemptCount;
    }

    public function lastAttemptAt(): ?DateTimeImmutable
    {
        return $this->lastAttemptAt;
    }

    public function sentAt(): ?DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function failureReason(): ?string
    {
        return $this->failureReason;
    }

    public function referenceType(): ?string
    {
        return $this->referenceType;
    }

    public function referenceId(): ?string
    {
        return $this->referenceId;
    }

    public function readAt(): ?DateTimeImmutable
    {
        return $this->readAt;
    }

    public function isRead(): bool
    {
        return $this->readAt !== null;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function markAsRead(): void
    {
        if ($this->channel !== NotificationChannel::IN_APP) {
            throw new CannotMarkReadNonInAppException('Solo notificaciones IN_APP pueden marcarse como leídas');
        }

        $this->readAt = new DateTimeImmutable();
    }

    public function markSent(): void
    {
        $this->status = NotificationStatus::SENT;
        $this->attemptCount++;
        $this->lastAttemptAt = new DateTimeImmutable();
        $this->sentAt = new DateTimeImmutable();
    }

    public function markFailed(string $reason): void
    {
        if ($this->status === NotificationStatus::SENT) {
            throw new InvalidNotificationStatusTransitionException('No se puede marcar como fallida una notificación ya enviada');
        }

        if ($this->status === NotificationStatus::FAILED) {
            return;
        }

        $this->attemptCount++;
        $this->lastAttemptAt = new DateTimeImmutable();
        $this->failureReason = $reason;

        if ($this->attemptCount >= 3) {
            $this->status = NotificationStatus::FAILED;
        }
    }

    public function canRetry(): bool
    {
        return $this->status !== NotificationStatus::SENT && $this->attemptCount < 3;
    }

    public function retry(): void
    {
        $this->status = NotificationStatus::PENDING;
        $this->attemptCount = 0;
        $this->failureReason = null;
    }
}
