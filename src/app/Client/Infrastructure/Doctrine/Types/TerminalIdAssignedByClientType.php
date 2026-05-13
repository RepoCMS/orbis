<?php

declare(strict_types=1);

namespace App\Client\Infrastructure\Doctrine\Types;

use App\Client\Domain\ValueObjects\TerminalIdAssignedByClientValueObject;
use App\Shared\Infrastructure\Doctrine\Types\AbstractStringValueObjectType;

final class TerminalIdAssignedByClientType extends AbstractStringValueObjectType
{
    public function getName(): string
    {
        return 'terminal_id_assigned_by_client';
    }

    protected function getValueObjectClass(): string
    {
        return TerminalIdAssignedByClientValueObject::class;
    }
}
