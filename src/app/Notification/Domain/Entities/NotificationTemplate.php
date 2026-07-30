<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entities;

use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Exceptions\EmptyBodyException;
use App\Notification\Domain\ValueObjects\NotificationTemplateIdValueObject;
use App\Shared\Domain\Contracts\TimestampableInterface;
use DateTimeImmutable;

final class NotificationTemplate implements TimestampableInterface
{
    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt = null;

    private function __construct(
        private NotificationTemplateIdValueObject $id,
        private EventType $eventType,
        private NotificationChannel $channel,
        private ?string $subject,
        private string $bodyTemplate,
        private bool $isActive = true,
    ) {
        if (trim($bodyTemplate) === '') {
            throw new EmptyBodyException('El cuerpo de la plantilla es obligatorio');
        }

        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(
        NotificationTemplateIdValueObject $id,
        EventType $eventType,
        NotificationChannel $channel,
        ?string $subject,
        string $bodyTemplate,
        bool $isActive = true,
    ): self {
        return new self($id, $eventType, $channel, $subject, $bodyTemplate, $isActive);
    }

    public function id(): NotificationTemplateIdValueObject
    {
        return $this->id;
    }

    public function eventType(): EventType
    {
        return $this->eventType;
    }

    public function channel(): NotificationChannel
    {
        return $this->channel;
    }

    public function subject(): ?string
    {
        return $this->subject;
    }

    public function bodyTemplate(): string
    {
        return $this->bodyTemplate;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function update(?string $subject, string $bodyTemplate, bool $isActive): void
    {
        if (trim($bodyTemplate) === '') {
            throw new EmptyBodyException('El cuerpo de la plantilla es obligatorio');
        }

        $this->subject = $subject;
        $this->bodyTemplate = $bodyTemplate;
        $this->isActive = $isActive;
    }
}
