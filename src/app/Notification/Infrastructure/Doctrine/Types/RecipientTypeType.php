<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Doctrine\Types;

use App\Notification\Domain\Enums\RecipientType;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class RecipientTypeType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'notification_recipient_type';
    }

    protected function getEnumClass(): string
    {
        return RecipientType::class;
    }
}
