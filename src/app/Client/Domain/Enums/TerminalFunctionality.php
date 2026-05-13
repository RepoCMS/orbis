<?php

declare(strict_types=1);

namespace App\Client\Domain\Enums;

enum TerminalFunctionality: string
{
    case Dispenser = 'dispenser';
    case Recycler = 'recycler';
    case Multifunction = 'multinfunction';
    case TCR = 'counter';
}
