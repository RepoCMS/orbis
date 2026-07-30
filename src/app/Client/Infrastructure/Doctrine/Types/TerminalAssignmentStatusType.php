<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\Enums\TerminalAssignmentStatus;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringEnumType;

final class TerminalAssignmentStatusType extends AbstractStringEnumType
{
    public function getName(): string
    {
        return 'terminal_assignment_status';
    }

    protected function getEnumClass(): string
    {
        return TerminalAssignmentStatus::class;
    }
}
