<?php

declare(strict_types=1);

namespace App\Notification\Domain\Entities;

use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\Enums\RecipientType;
use App\Notification\Domain\Exceptions\CannotDisableInAppException;
use App\Notification\Domain\ValueObjects\NotificationPreferenceIdValueObject;

final class NotificationPreference
{
    private function __construct(
        private NotificationPreferenceIdValueObject $id,
        private RecipientType $recipientType,
        private string $recipientId,
        private NotificationChannel $channel,
        private EventType $eventType,
        private bool $isEnabled = true,
    ) {
    }

    public static function create(
        NotificationPreferenceIdValueObject $id,
        RecipientType $recipientType,
        string $recipientId,
        NotificationChannel $channel,
        EventType $eventType,
        bool $isEnabled = true,
    ): self {
        return new self($id, $recipientType, $recipientId, $channel, $eventType, $isEnabled);
    }

    public function id(): NotificationPreferenceIdValueObject
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

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function toggle(bool $isEnabled): void
    {
        if ($this->recipientType === RecipientType::EMPLOYEE && $this->channel === NotificationChannel::IN_APP && !$isEnabled) {
            throw new CannotDisableInAppException('Las notificaciones IN_APP son obligatorias para empleados');
        }

        $this->isEnabled = $isEnabled;
    }
}
