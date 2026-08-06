<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\Enums\TerminalVendor;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class TerminalVendorType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'terminal_vendor';
    }
    protected function getEnumClass(): string
    {
        return TerminalVendor::class;
    }
}
