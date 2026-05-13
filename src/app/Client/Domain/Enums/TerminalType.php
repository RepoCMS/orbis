<?php

declare(strict_types=1);

namespace App\Client\Domain\Enums;

enum TerminalType: string
{
    case Lobby = 'lobby';
    case TTW = 'ttw';
}
