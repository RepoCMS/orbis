<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Doctrine\Types;

use App\Notification\Domain\ValueObjects\NotificationTemplateIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class NotificationTemplateIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'notification_template_id';
    }

    protected function getValueObjectClass(): string
    {
        return NotificationTemplateIdValueObject::class;
    }
}
