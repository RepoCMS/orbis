<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\Enums\TerminalFunctionality;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class TerminalFunctionalityType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'terminal_functionality';
    }
    protected function getEnumClass(): string
    {
        return TerminalFunctionality::class;
    }
}
