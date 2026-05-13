<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine\Types;

use App\Identity\Domain\Enums\UserStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class UserStatusType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'user_status';
    }

    protected function getEnumClass(): string
    {
        return UserStatus::class;
    }
}
