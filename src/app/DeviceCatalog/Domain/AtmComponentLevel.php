<?php

declare(strict_types=1);

namespace App\DeviceCatalog\Domain;

enum AtmComponentLevel: string
{
    case Device = 'device';
    case Module = 'module';
    case Part   = 'part';
}
