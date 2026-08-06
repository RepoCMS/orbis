<?php

declare(strict_types=1);

namespace App\Support\Infrastructure\Doctrine\Types;

use App\Shared\Infrastructure\Doctrine\Types\AbstractUlidType;
use App\Support\Domain\ValueObjects\TicketEvidenceIdValueObject;

final class TicketEvidenceIdType extends AbstractUlidType
{
    public function getName(): string
    {
        return 'ticket_evidence_id';
    }
    protected function getValueObjectClass(): string
    {
        return TicketEvidenceIdValueObject::class;
    }
}
