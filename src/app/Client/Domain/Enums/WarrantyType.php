<?php

declare(strict_types=1);

namespace App\Client\Domain\Enums;

enum WarrantyType: string
{
    case MANUFACTURER = 'manufacturer';
    case EXTENDED = 'extended';
}
