<?php

declare(strict_types=1);

namespace App\Client\Domain\Enums;

enum ContactStatus: string
{
    case ACTIVE   = 'active';
    case INACTIVE = 'inactive';
}
