<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\ValueObjects\TerminalAssignmentIdValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;

final class TerminalAssignmentIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'terminal_assignment_id';
    }

    protected function getValueObjectClass(): string
    {
        return TerminalAssignmentIdValueObject::class;
    }
}
