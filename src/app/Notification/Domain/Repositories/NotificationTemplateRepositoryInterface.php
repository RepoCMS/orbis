<?php

declare(strict_types=1);

namespace App\Notification\Domain\Repositories;

use App\Notification\Domain\Entities\NotificationTemplate;
use App\Notification\Domain\Enums\EventType;
use App\Notification\Domain\Enums\NotificationChannel;
use App\Notification\Domain\ValueObjects\NotificationTemplateIdValueObject;

interface NotificationTemplateRepositoryInterface
{
    public function findById(NotificationTemplateIdValueObject $id): ?NotificationTemplate;
    public function findByEventTypeAndChannel(EventType $eventType, NotificationChannel $channel): ?NotificationTemplate;
    public function findAll(?EventType $eventType = null, ?NotificationChannel $channel = null, ?bool $isActive = null): array;
    public function save(NotificationTemplate $template): void;
}
