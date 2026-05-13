<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\ValueObjects\TerminalNameValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class TerminalNameType extends AbstractStringValueObjectType
{
    public function getName(): string
    {
        return 'terminal_name';
    }

    protected function getValueObjectClass(): string
    {
        return TerminalNameValueObject::class;
    }
}
