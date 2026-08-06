<?php

declare(strict_types=1);

namespace App\Support\Application\Query\GetEvidence;

use App\Support\Application\Exception\EvidenceNotFoundException;
use App\Support\Application\Query\Response\TicketEvidenceResponse;
use App\Support\Domain\Repositories\TicketEvidenceRepositoryInterface;
use App\Support\Domain\ValueObjects\TicketEvidenceIdValueObject;

final readonly class GetEvidenceHandler
{
    public function __construct(
        private TicketEvidenceRepositoryInterface $evidenceRepo,
    ) {
    }

    public function __invoke(GetEvidenceQuery $query): TicketEvidenceResponse
    {
        $evidence = $this->evidenceRepo->findById(
            TicketEvidenceIdValueObject::fromString($query->evidenceId)
        );

        if ($evidence === null) {
            throw EvidenceNotFoundException::withId($query->evidenceId);
        }

        return TicketEvidenceResponse::fromEntity($evidence);
    }
}
