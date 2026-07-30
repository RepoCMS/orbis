<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\ValueObjects\TerminalModelIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class TerminalModelIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'terminal_model_id';
    }

    protected function getValueObjectClass(): string
    {
        return TerminalModelIdValueObject::class;
    }
}
