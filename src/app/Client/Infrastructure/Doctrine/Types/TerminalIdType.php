<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class TerminalIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'terminal_id';
    }

    protected function getValueObjectClass(): string
    {
        return TerminalIdValueObject::class;
    }
}
