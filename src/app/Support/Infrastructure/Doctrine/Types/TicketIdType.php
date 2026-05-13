<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final class TicketIdType extends AbstractUlidType
{
    public function getName(): string { return 'ticket_id'; }
    protected function getValueObjectClass(): string { return TicketIdValueObject::class; }
}
