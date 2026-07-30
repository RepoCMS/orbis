<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine\Types;

use App\Shared\Domain\ValueObjects\ProcessedCommandId;

final class ProcessedCommandIdType extends AbstractUlidType
{
    protected function getValueObjectClass(): string
    {
        return ProcessedCommandId::class;
    }
}
