<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Doctrine\Types;

use App\Notification\Domain\ValueObjects\NotificationPreferenceIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class NotificationPreferenceIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'notification_preference_id';
    }

    protected function getValueObjectClass(): string
    {
        return NotificationPreferenceIdValueObject::class;
    }
}
