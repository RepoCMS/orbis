<?php

declare(strict_types=1);

namespace App\Notification\Domain\ValueObjects;

use App\Notification\Domain\Exceptions\NotificationTemplateIdException;
use App\Shared\Domain\AbstractUlid;
use App\Shared\Exceptions\DomainException;

final readonly class NotificationTemplateIdValueObject extends AbstractUlid
{
    protected static function invalidIdException(): DomainException
    {
        return new NotificationTemplateIdException('Formato de ULID inválido para NotificationTemplateId.');
    }
}
