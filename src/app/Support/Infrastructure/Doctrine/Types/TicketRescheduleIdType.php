<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Support\Domain\ValueObjects\TicketRescheduleIdValueObject;

final class TicketRescheduleIdType extends AbstractUlidType
{
    public function getName(): string { return 'ticket_reschedule_id'; }
    protected function getValueObjectClass(): string { return TicketRescheduleIdValueObject::class; }
}
