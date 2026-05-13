<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine\Types;

use App\Identity\Domain\ValueObjects\UserMfaConfigIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class UserMfaConfigIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'user_mfa_config_id';
    }

    protected function getValueObjectClass(): string
    {
        return UserMfaConfigIdValueObject::class;
    }
}
