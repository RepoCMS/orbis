<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Identity\Domain\ValueObjects\UserIdValueObject;

final class UserIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'user_id';
    }

    protected function getValueObjectClass(): string
    {
        return UserIdValueObject::class;
    }
}
