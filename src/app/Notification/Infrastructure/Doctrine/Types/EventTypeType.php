<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Doctrine\Types;

use App\Notification\Domain\Enums\EventType;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class EventTypeType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'notification_event_type';
    }

    protected function getEnumClass(): string
    {
        return EventType::class;
    }
}
