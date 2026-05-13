<?php

declare(strict_types=1);

namespace App\Client\Domain\Enums;

enum TerminalAssignmentStatus: string
{
    case OPERATIVE = 'operative';
    case INOPERATIVE = 'inoperative';
}
