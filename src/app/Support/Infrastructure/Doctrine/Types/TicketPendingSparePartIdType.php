<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Support\Domain\ValueObjects\TicketPendingSparePartIdValueObject;

final class TicketPendingSparePartIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'ticket_pending_spare_part_id';
    }
    protected function getValueObjectClass(): string
    {
        return TicketPendingSparePartIdValueObject::class;
    }
}
