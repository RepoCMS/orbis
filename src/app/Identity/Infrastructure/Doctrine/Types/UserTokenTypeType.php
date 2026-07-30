<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine\Types;

use App\Identity\Domain\Enums\UserTokenType;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class UserTokenTypeType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'user_token_type';
    }

    protected function getEnumClass(): string
    {
        return UserTokenType::class;
    }
}
