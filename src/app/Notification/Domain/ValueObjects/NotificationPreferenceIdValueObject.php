<?php

declare(strict_types=1);

namespace App\Notification\Domain\ValueObjects;

use App\Notification\Domain\Exceptions\NotificationPreferenceIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class NotificationPreferenceIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new NotificationPreferenceIdException('Formato de ULID inválido para NotificationPreferenceId.');
    }
}
