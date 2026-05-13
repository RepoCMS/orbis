<?php

declare(strict_types=1);

namespace App\Client\Domain\Enums;

enum SiteStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
