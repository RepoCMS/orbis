<?php

declare(strict_types=1);

namespace App\Identity\Domain\Enums;

enum UserTokenType: string
{
    case ACTIVATION      = 'ACTIVATION';
    case PASSWORD_RESET  = 'PASSWORD_RESET';
    case ACCOUNT_UNLOCK  = 'ACCOUNT_UNLOCK';
}
