<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Doctrine\Types;

use App\Notification\Domain\ValueObjects\NotificationLogIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class NotificationLogIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'notification_log_id';
    }

    protected function getValueObjectClass(): string
    {
        return NotificationLogIdValueObject::class;
    }
}
