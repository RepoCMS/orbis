<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Support\Domain\ValueObjects\TicketWaitingIdValueObject;

final class TicketWaitingIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'ticket_waiting_id';
    }
    protected function getValueObjectClass(): string
    {
        return TicketWaitingIdValueObject::class;
    }
}
