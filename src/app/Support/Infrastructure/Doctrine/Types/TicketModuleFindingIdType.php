<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Support\Domain\ValueObjects\TicketModuleFindingIdValueObject;

final class TicketModuleFindingIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'ticket_module_finding_id';
    }

    protected function getValueObjectClass(): string
    {
        return TicketModuleFindingIdValueObject::class;
    }
}
