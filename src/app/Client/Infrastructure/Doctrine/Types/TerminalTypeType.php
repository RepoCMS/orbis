<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\Enums\TerminalType;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class TerminalTypeType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'terminal_type';
    }
    protected function getEnumClass(): string
    {
        return TerminalType::class;
    }
}
