<?php

declare(strict_types=1);

namespace App\Support\Domain\Repositories;

use App\Support\Domain\Entities\TicketEvidence;
use App\Support\Domain\ValueObjects\TicketEvidenceIdValueObject;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

interface TicketEvidenceRepositoryInterface
{
    public function findById(TicketEvidenceIdValueObject $id): ?TicketEvidence;

    /** @return TicketEvidence[] */
    public function findByTicket(TicketIdValueObject $ticketId): array;

    public function save(TicketEvidence $evidence): void;

    public function delete(TicketEvidenceIdValueObject $id): void;
}
