<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\Enums\TerminalStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class TerminalStatusType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'terminal_status';
    }
    protected function getEnumClass(): string
    {
        return TerminalStatus::class;
    }
}
