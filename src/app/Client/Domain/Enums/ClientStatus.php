<?php

declare(strict_types=1);

namespace App\Client\Domain\Enums;

enum ClientStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
