<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine\Types;

use App\Identity\Domain\ValueObjects\UserTokenIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class UserTokenIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'user_token_id';
    }

    protected function getValueObjectClass(): string
    {
        return UserTokenIdValueObject::class;
    }
}
