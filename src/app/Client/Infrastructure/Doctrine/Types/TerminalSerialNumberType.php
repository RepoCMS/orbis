<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\ValueObjects\SerialNumberValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class TerminalSerialNumberType extends AbstractStringValueObjectType
{
    public function getName(): string
    {
        return 'terminal_serial';
    }

    protected function getValueObjectClass(): string
    {
        return SerialNumberValueObject::class;
    }
}
