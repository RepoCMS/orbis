<?php

declare(strict_types=1);

namespace App\Support\Application\Query\ListEvidences;

use App\Support\Application\Query\Response\TicketEvidenceResponse;
use App\Support\Domain\Repositories\TicketEvidenceRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketIdValueObject;

final readonly class ListEvidencesHandler
{
    public function __construct(
        private TicketEvidenceRepositoryInterface $evidenceRepo,
    ) {
    }

    /** @return TicketEvidenceResponse[] */
    public function __invoke(ListEvidencesQuery $query): array
    {
        $evidences = $this->evidenceRepo->findByTicket(
            TicketIdValueObject::fromString($query->ticketId)
        );

        return array_map(
            fn ($evidence) => TicketEvidenceResponse::fromEntity($evidence),
            $evidences
        );
    }
}
