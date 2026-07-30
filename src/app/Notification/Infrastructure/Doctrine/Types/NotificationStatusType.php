<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Doctrine\Types;

use App\Notification\Domain\Enums\NotificationStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class NotificationStatusType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'notification_status';
    }

    protected function getEnumClass(): string
    {
        return NotificationStatus::class;
    }
}
