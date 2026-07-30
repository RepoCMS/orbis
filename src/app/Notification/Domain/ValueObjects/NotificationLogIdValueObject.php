<?php

declare(strict_types=1);

namespace App\Notification\Domain\ValueObjects;

use App\Notification\Domain\Exceptions\NotificationLogIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class NotificationLogIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new NotificationLogIdException('Formato de ULID inválido para NotificationLogId.');
    }
}
