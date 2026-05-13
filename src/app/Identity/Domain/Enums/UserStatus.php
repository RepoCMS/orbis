<?php

declare(strict_types=1);

namespace App\Identity\Domain\Enums;

enum UserStatus: string
{
    case UNVERIFIED = 'unverified';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
