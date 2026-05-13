<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Support\Domain\ValueObjects\TicketPauseIdValueObject;

final class TicketPauseIdType extends AbstractUlidType
{
    public function getName(): string { return 'ticket_pause_id'; }
    protected function getValueObjectClass(): string { return TicketPauseIdValueObject::class; }
}
