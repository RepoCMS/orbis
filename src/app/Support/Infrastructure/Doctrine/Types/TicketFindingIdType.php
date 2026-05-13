<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Support\Domain\ValueObjects\TicketFindingIdValueObject;

final class TicketFindingIdType extends AbstractUlidType
{
    public function getName(): string { return 'ticket_finding_id'; }
    protected function getValueObjectClass(): string { return TicketFindingIdValueObject::class; }
}
