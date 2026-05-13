<?php

declare(strict_types=1);

namespace App\Client\Domain\Enums;

enum TerminalVendor: string
{
    case Hyosung = 'hyosung';
    case Diebold_NIXDORF = 'diebold';
    case NCR = 'ncr';
    case GRG = 'grg';
}
