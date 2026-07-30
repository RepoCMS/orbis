<?php

declare(strict_types=1);

namespace App\Client\Domain\Enums;

enum ContractStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
}
