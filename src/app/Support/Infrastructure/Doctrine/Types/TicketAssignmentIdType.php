<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Support\Domain\ValueObjects\TicketAssignmentIdValueObject;

final class TicketAssignmentIdType extends AbstractUlidType
{
    public function getName(): string { return 'ticket_assignment_id'; }
    protected function getValueObjectClass(): string { return TicketAssignmentIdValueObject::class; }
}
